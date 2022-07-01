<?php

namespace QT\Foundation\GraphQL\Definition;

use Illuminate\Support\Str;
use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Definition\Type;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ListOfType;
use QT\Foundation\Export\ExcelGenerator;
use GraphQL\Type\Definition\Type as BaseType;
use QT\GraphQL\Definition\ModelType as BaseModelType;

/**
 * ModelType
 *
 * @package QT\Foundation\GraphQL\Definition
 */
abstract class ModelType extends BaseModelType
{
    /**
     * 允许访问的字段
     *
     * @var array
     */
    public $canAccess = [];

    /**
     * 是否启用导出类型
     *
     * @var bool
     */
    public $useExport = false;

    /**
     * 查询单条记录时的主键名称
     *
     * @var string
     */
    protected $keyName = 'id';

    /**
     * 主键类型
     *
     * @var string
     */
    protected $keyType = 'bigint';

    /**
     * 获取model可用字段,允许继承细分可用字段
     *
     * @return array
     */
    protected function getModelFields(): array
    {
        $fields = $this->getDataStructure($this->manager);

        if (empty($this->canAccess)) {
            return $fields;
        }

        return $this->defineAccessFields($fields, $this->canAccess);
    }

    /**
     * 根据允许访问的字段构建字段
     *
     * @param array $fields
     * @param array $canAccess
     * @param string $prefix
     * @return array
     */
    protected function defineAccessFields(array $fields, array $canAccess, string $prefix = ''): array
    {
        if (isset($canAccess['*'])) {
            // 允许全部字段访问时,根据可访问字段构建
            foreach (array_keys($fields) as $field) {
                if (empty($canAccess[$field])) {
                    $canAccess[$field] = true;
                }
            }
        }

        $results = [];
        foreach ($canAccess as $field => $child) {
            // 字段不存在
            if (empty($fields[$field])) {
                continue;
            }

            if ($fields[$field] instanceof BaseType) {
                $fieldDef = ['type' => $fields[$field]];
            } elseif (is_array($fields[$field]) && !empty($fields[$field]['type'])) {
                $fieldDef = $fields[$field];
            }

            [$type, $wrap] = $this->unwrap($fieldDef['type']);

            if (!$type instanceof ModelType) {
                $results[$field] = $fieldDef;
                continue;
            } elseif (!is_array($child)) {
                continue;
            }

            if ($prefix === '') {
                $prefix = $this->name;
            }

            // 根据配置生成可访问字段
            $func = fn () => $this->defineAccessFields(
                $type->getDataStructure($this->manager),
                $child,
                "{$prefix}_{$field}"
            );

            $results[$field] = $wrap($this->manager->create(
                Str::camel("{$prefix}_{$field}"),
                $func
            ));
        }

        return $results;
    }

    /**
     * 如果type有包装,将其包装解除并返回包装回调函数
     *
     * @param Type $type
     * @return array
     */
    protected function unwrap($type): array
    {
        $wrap = function ($type) {
            return $type;
        };
        if ($type instanceof ListOfType) {
            $type = $type->getOfType();
            $wrap = function ($type) {
                return Type::listOf($type);
            };
        } elseif ($type instanceof NonNull) {
            $type = $type->getOfType();
            $wrap = function ($type) {
                return Type::nonNull($type);
            };
        }

        return [$type, $wrap];
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
            $this->keyName => [
                'type'        => Type::{$this->keyType}(),
                'description' => $this->keyName,
            ],
        ];
    }

    /**
     * 获取可导出字段
     * [
     *    'id' => '编号',
     *    'name' => '名称',
     *    'relation1.name' => '关联1的名称',
     *    'relation2' => [
     *        'name' => '关联2的名称',
     *    ],
     * ]
     *
     * @return array
     */
    public function getExportColumns(): array
    {
        return [];
    }

    /**
     * excel导出生成器
     *
     * @param array $selected
     * @param array $alias
     * @param array $filters
     * @param array $orderBy
     * @return ExcelGenerator
     */
    public function getExportGenerator(array $selected, array $alias, array $filters, array $orderBy): ExcelGenerator
    {
        return new ExcelGenerator($selected, $this->getExportColumns(), $alias, $filters, $orderBy);
    }
}
