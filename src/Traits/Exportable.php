<?php

namespace QT\Foundation\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * 可导出的model
 * 
 * @package QT\Foundation\Traits
 */
trait Exportable
{
    use Enumerable;

    /**
     * 导出时每一个字段对应的format逻辑
     * 
     * @var array
     */
    protected static $handlers = [];

    /**
     * 获取导出字段处理方式
     *
     * @return array
     */
    public static function getExportHandler(): array
    {
        if (!empty(static::$handlers)) {
            return static::$handlers;
        }

        // 自动转换枚举字段
        foreach (static::getEnums() as $field => $dictionary) {
            static::$handlers[$field] = array_flip($dictionary->all());
        }

        return static::$handlers;
    }

    /**
     * 格式化model
     *
     * @param array $fields
     * @return array
     */
    public function formatData($fields = [])
    {
        $result = [];
        $fields = $fields ?: $this->getSelected();
        foreach ($fields as $field => $selected) {
            $result[$field] = $this->getValue($field, $selected);
        }

        return $result;
    }

    /**
     * 获取已选中的字段
     *
     * @return array
     */
    public function getSelected()
    {
        // 把attributes变为[column => true]格式
        $selected = array_combine(
            array_keys($this->attributes),
            array_pad([], count($this->attributes), true)
        );

        foreach ($this->relations as $key => $relation) {
            if ($relation instanceof Collection) {
                $relation = $relation->first();
            }

            if (!$relation instanceof Model) {
                continue;
            }

            $selected[$key] = $relation->getSelected();
        }

        return $selected;
    }

    /**
     * @param string $field
     * @param array  $selected
     * @return string|Model|Collection
     */
    protected function getValue(string $field, array $selected)
    {
        $value    = $this->getAttribute($field);
        $handlers = static::getExportHandler();

        if (array_key_exists($field, $handlers)) {
            if (is_callable($handlers[$field])) {
                // 使用自定义方法进行处理
                $value = call_user_func($handlers[$field], $this, $value);
            } elseif (is_array($handlers[$field])) {
                // 根据字典值进行转换,如果没有对应字典值返回空字符
                return $handlers[$field][$value] ?? '';
            }
        }

        if ($value instanceof self) {
            return $value->formatData($selected);
        }

        if ($value instanceof Collection) {
            return $value->map(function ($model) use ($selected) {
                return $model->formatData($selected);
            });
        }

        return $value;
    }
}