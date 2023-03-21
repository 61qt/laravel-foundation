<?php

namespace QT\Foundation\GraphQL\Definition;

use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Definition\Type;
use QT\Foundation\Export\ExcelGenerator;
use QT\GraphQL\Definition\ModelType as BaseModelType;

/**
 * ModelType
 *
 * @package QT\Foundation\GraphQL\Definition
 */
abstract class ModelType extends BaseModelType
{
    use WhitelistFields;

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
