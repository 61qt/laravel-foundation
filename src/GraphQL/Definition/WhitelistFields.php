<?php

namespace QT\Foundation\GraphQL\Definition;

use QT\GraphQL\Definition\Type;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Error\InvariantViolation;
use QT\GraphQL\Definition\ObjectType;
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
     * 允许访问的字段
     *
     * @var array
     */
    protected $whitelist = [];

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

            $fieldDef = $fields[$field];

            [$type, $wrap] = $this->unwrap($fieldDef->getType());

            if (!is_array($value) || !$type instanceof ObjectType) {
                $results[$field] = $fieldDef;
                continue;
            }

            $name   = $prefix . ucfirst($field);
            $func   = fn () => $this->defineAccessFields($type->getOriginalFields(), $value, $name);
            $desc   = $fieldDef->description;
            $struct = $this->getChildStruct($name, $func, $desc, $type->resolveFieldFn);

            $results[$field] = FieldDefinition::create(array_merge($fields[$field]->config, [
                'description' => $desc,
                'type'        => $wrap($this->manager->setType($struct)),
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
     * 获取子级对象结构
     *
     * @param string $name
     * @param callable|array $fields
     * @param string $desc
     * @param callable $resolveFieldFn
     */
    protected function getChildStruct(string $name, callable|array $fields, string $desc, callable $resolveFieldFn)
    {
        return new ObjectType([
            'name'         => $name,
            'fields'       => $fields,
            'description'  => $desc,
            'resolveField' => $resolveFieldFn,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name
     * @return FieldDefinition
     */
    public function getField(string $name): FieldDefinition
    {
        if (!isset($this->whitelist[$name])) {
            throw new InvariantViolation(sprintf('Field "%s" is not defined for type "%s"', $name, $this->name));
        }

        return parent::getField($name);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name
     * @return ?FieldDefinition
     */
    public function findField(string $name): ?FieldDefinition
    {
        if (!isset($this->whitelist[$name])) {
            return null;
        }

        return parent::findField($name);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name
     * @return bool
     */
    public function hasField(string $name): bool
    {
        if (!isset($this->whitelist[$name])) {
            return false;
        }

        return parent::hasField($name);
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function getFields(): array
    {
        $fields = parent::getFields();

        return $this->defineAccessFields($fields, $this->whitelist, $this->name);
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function getFieldNames(): array
    {
        return array_intersect(parent::getFieldNames(), array_keys($this->whitelist));
    }
}
