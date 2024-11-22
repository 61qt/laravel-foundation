<?php

namespace QT\Foundation\GraphQL\Definition;

use QT\GraphQL\Definition\Type;
use GraphQL\Type\Definition\NonNull;
use QT\GraphQL\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\FieldDefinition;

/**
 * WhitelistFields
 *
 * @package QT\Foundation\GraphQL\Definition
 */
trait WhitelistFields
{
    /**
     * 字段白名单
     *
     * @var array
     */
    protected $whitelist = [];

    /**
     * 允许访问的字段
     *
     * @var array
     */
    protected $fields;

    /**
     * 设置可访问字段
     *
     * @param array $whitelist
     */
    public function setWhitelistFields(array $whitelist)
    {
        $this->whitelist = $whitelist;
    }

    /**
     * 根据允许访问的字段构建字段
     *
     * @param array $fields
     * @param array $whitelist
     * @param string $prefix
     * @return array
     */
    protected function defineAccessFields(array $fields, array $whitelist, string $prefix): array
    {
        if (isset($whitelist['*'])) {
            // 允许全部字段访问时,根据可访问字段构建
            foreach ($fields as $field => $_) {
                if (empty($whitelist[$field])) {
                    $whitelist[$field] = true;
                }
            }
        }

        $results = [];
        foreach ($whitelist as $field => $value) {
            // 字段不存在
            if (empty($fields[$field])) {
                continue;
            }

            [$type, $wrap] = $this->unwrap($fields[$field]->getType());

            if (!is_array($value)) {
                if (!$type instanceof UnionType && !$type instanceof ObjectType) {
                    $results[$field] = $fields[$field];
                }
                continue;
            }

            $typeName = $this->getTypeName($prefix, $field);
            if ($type instanceof UnionType) {
                $type = $this->getUnionType($typeName, $type, $value);
            } elseif ($type instanceof ObjectType) {
                $type = $this->getFieldDefinition($typeName, $type, $value);
            }

            $results[$field] = FieldDefinition::create(array_merge($fields[$field]->config, [
                'type' => $wrap($this->manager->setType($type)),
            ]));
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
        $wrap = fn ($type) => $type;

        if ($type instanceof ListOfType) {
            $type = $type->getOfType();
            $wrap = fn ($type) => Type::listOf($type);
        } elseif ($type instanceof NonNull) {
            $type = $type->getOfType();
            $wrap = fn ($type) => Type::nonNull($type);
        }

        return [$type, $wrap];
    }

    /**
     * @param string $name
     * @param UnionType $unionType
     * @param array $whitelist
     * @return UnionType
     */
    protected function getUnionType(string $name, UnionType $unionType, array $whitelist): UnionType
    {
        $types = [];
        foreach ($unionType->getTypes() as $type) {
            if (!isset($whitelist[$type->name]) || !is_array($whitelist[$type->name])) {
                continue;
            }

            $newType = $this->getFieldDefinition($this->getTypeName($name, $type->name), $type, $whitelist[$type->name]);

            $types[$type->name] = $this->manager->setType($newType);
        }

        $resolveTypeFn = null;
        if (!empty($unionType->config['resolveType'])) {
            $resolveTypeFn = function ($value) use ($unionType, $types) {
                $type = call_user_func($unionType->config['resolveType'], $value);

                return $types[$type?->name];
            };
        }

        return new UnionType([
            'name'        => $name,
            'types'       => array_values($types),
            'resolveType' => $resolveTypeFn,
        ]);
    }

    /**
     * 根据字段白名单生成对象结构
     *
     * @param string $name
     * @param ObjectType $parentType
     * @param array $whitelist
     * @param string $prefix
     * @return ObjectType
     */
    protected function getFieldDefinition(string $name, ObjectType $parentType, array $whitelist): ObjectType
    {
        $func = fn () => $this->defineAccessFields($parentType->getOriginalFields(), $whitelist, $name);

        return $this->getChildStruct($name, $func, $parentType->resolveFieldFn);
    }

    /**
     * 获取子级对象结构
     *
     * @param string $name
     * @param callable|array $fields
     * @param callable $resolveFieldFn
     */
    protected function getChildStruct(string $name, callable|array $fields, callable $resolveFieldFn)
    {
        return new ObjectType([
            'name'         => $name,
            'fields'       => $fields,
            'resolveField' => $resolveFieldFn,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function getFields(): array
    {
        if (empty($this->whitelist)) {
            return parent::getFields();
        }

        if (!isset($this->fields)) {
            $this->fields = $this->defineAccessFields(parent::getFields(), $this->whitelist, $this->name);
        }

        return $this->fields;
    }

    /**
     * 获取type的名称，需要模块内唯一，typeName+relation的typeName
     *
     * @param string $prefix
     * @param string $subType
     * @return string
     */
    protected function getTypeName(string $prefix, string $subType): string
    {
        return $prefix . '_' . ucfirst($subType);
    }
}
