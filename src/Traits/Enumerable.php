<?php

namespace QT\Foundation\Traits;

use ReflectionClass;
use Illuminate\Support\Str;
use QT\Foundation\Dictionaries\Dictionary;

/**
 * 可枚举的model
 * 
 * @package QT\Foundation\Traits
 */
trait Enumerable
{
    /**
     * @var array
     */
    protected static $enums = [];

    /**
     * 没有设置枚举的字段用的默认值
     * 
     * @return array
     */
    protected static function getDefaultDict(): array
    {
        return [];
    }

    /**
     * 获取所有枚举字段
     *
     * @return array<string, Dictionary>
     */
    public static function getEnums(): array
    {
        $results = [];
        $reflect = new ReflectionClass(static::class);
        foreach (static::$enums as $field) {
            $column = Str::camel($field);
            $name   = Str::camel("{$field}_maps");

            if (method_exists(static::class, "get{$column}Dictionary")) {
                $value = static::{"get{$column}Dictionary"}();
            } else {
                $value = $reflect->getStaticPropertyValue($name);
            }

            if ($value === null) {
                $value = static::getDefaultDict();
            }

            $results[$field] = new Dictionary($field, array_flip($value));
        }

        return $results;
    }
}
