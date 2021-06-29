<?php

namespace QT\Foundation\GraphQL\Type;

use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Definition\Type;
use QT\GraphQL\Definition\ModelType as BaseModelType;

/**
 * ModelType
 *
 * @package QT\Foundation\GraphQL\Type
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

        // TODO 生成可访问字段

        return $fields;
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
            'id' => [
                'type'        => Type::bigint(),
                'description' => 'id',
            ],
        ];
    }
}
