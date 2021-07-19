<?php

namespace QT\Foundation\GraphQL\Definition;

use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Definition\Type;
use QT\Foundation\Auth\Resource;
use QT\GraphQL\Contracts\Context;
use QT\Foundation\Exceptions\Error;
use QT\GraphQL\Contracts\Resolvable;
use QT\GraphQL\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use QT\Foundation\Export\ExcelGenerator;

/**
 * ExportType
 *
 * @package QT\Foundation\GraphQL\Definition
 */
abstract class ExportType extends ObjectType implements Resolvable
{
    /**
     * @var ModelType
     */
    public $ofType;

    /**
     * @var ObjectType
     */
    public $returnType;

    /**
     * ExportType Constructor
     *
     * @param ModelType $type
     */
    public function __construct(ModelType $type, ObjectType $returnType)
    {
        $this->ofType     = $type;
        $this->returnType = $returnType;

        parent::__construct([
            'name'   => isset($this->name) ? $this->name : "{$type->name}Export",
            'fields' => [$this, 'getDataStructure'],
        ]);
    }

    /**
     * @return array
     */
    public function getDataStructure(): array
    {
        return $this->returnType->getFields();
    }

    /**
     * {@inheritDoc}
     *
     * @param GraphQLManager $manager
     * @return array
     */
    public function getArgs(GraphQLManager $manager): array
    {
        return [
            'exportColumns' => [
                'type'        => Type::listOf(Type::string()),
                'description' => '导出的字段',
            ],
            'filters'       => [
                'type'        => $this->ofType->getFiltersInput(),
                'description' => '查询条件',
            ],
            'exportAliases' => [
                'type'        => Type::json(),
                'description' => '导出字段别名',
            ],
            'name'          => [
                'type'         => Type::string(),
                'description'  => '下载任务名',
                'defaultValue' => $this->name,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $node
     * @param array $args
     * @param Context $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public function resolve(mixed $node, array $args, Context $context, ResolveInfo $info): mixed
    {
        $user = $context->getRequest()->user();

        if ($user === null || !Resource::isAllowExport($user, $context, $this->name)) {
            throw new Error('UNAUTH', '没有导出权限');
        }

        if (empty($args['exportColumns'])) {
            throw new Error('FORBIDDEN', '必选选中一个字段进行导出');
        }

        $resolver = $this->ofType->getResolver();
        $query    = $resolver->getModelQuery();
        $count    = $resolver->buildFilter($query, $args['filters'] ?? [])->count();

        if (isset($resolver->exportLimit)) {
            $maxLimit = $resolver->exportLimit;
        } else {
            $maxLimit = $context->getValue('graphql.export_limit');
        }

        if ($count == 0) {
            throw new Error('NOT_FOUND', '没有可导出的记录');
        }

        if ($count > $maxLimit) {
            throw new Error('FORBIDDEN', "最大允许导出{$maxLimit}条记录");
        }

        $generator = $this->ofType->getExportGenerator(
            $args['exportColumns'],
            $args['exportAliases'] ?? [],
            $args['filters'] ?? []
        );

        return $this->createTask($generator, $context, $count, $args);
    }

    /**
     * 创建导出任务
     *
     * @param ExcelGenerator $generator
     * @param Context $context
     * @param array array $args
     * @param int $total
     */
    abstract public function createTask(ExcelGenerator $generator, Context $context, int $total, array $args): mixed;
}
