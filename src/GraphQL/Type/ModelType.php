<?php

namespace QT\Foundation\GraphQL\Type;

use Illuminate\Support\Str;
use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Definition\Type;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type as BaseType;
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

        return $this->defineFieldMap($fields, $this->canAccess);
    }

    /**
     * 根据允许访问的字段构建字段
     *
     * @param array $fields
     * @param array $canAccess
     * @return array
     */
    protected function defineFieldMap(array $fields, array $canAccess)
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

            $type = $fieldDef['type'];
            if ($type instanceof ListOfType) {
                $type = $type->getOfType();
            } elseif ($type instanceof NonNull) {
                $type = $type->getOfType();
            }

            if (!$type instanceof ModelType) {
                $results[$field] = $fieldDef;
                continue;
            } elseif (!is_array($child)) {
                continue;
            }

            // 根据配置生成可访问字段
            $func = function () use ($type, $child) {
                return $this->defineFieldMap(
                    $type->getDataStructure($this->manager), $child
                );
            };

            $results[$field] = $this->manager->create(
                Str::camel("{$this->name}_{$field}_object"), $func
            );
        }

        return $results;
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
