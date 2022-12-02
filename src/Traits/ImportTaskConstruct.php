<?php

namespace QT\Foundation\Traits;

use QT\Foundation\Model;
use QT\Foundation\Exceptions\Error;

/**
 * 导入任务初始化
 *
 * @package QT\Foundation\Traits
 */
trait ImportTaskConstruct
{
    /**
     * Import task constructor.
     */
    public function __construct()
    {
        $this->bootModelDictionary();
    }

    /**
     * 获取Resolver绑定的model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function bootModelDictionary()
    {
        $this->model = !empty($this->model) ? $this->model : '';
        if (empty($this->enums)) {
            if (empty($this->model) || !is_subclass_of($this->model, Model::class)) {
                throw new Error('SYSTEM_FAILED', static::class . '没有配置model');
            }

            $this->enums = array_keys($this->model::$enums);
        }

        $maps = [];
        foreach ($this->enums as $field => $dictField) {
            if (is_int($field) && is_string($dictField)) {
                $field     = $dictField;
                $dictField = [$this->model, $field];
            }

            list($class, $dictField) = $dictField;

            if (!is_subclass_of($class, Model::class)) {
                throw new Error('SYSTEM_FAILED', "无法从{$class}获取字典");
            }

            if (empty($maps[$class])) {
                $maps[$class] = $class::getEnums();
            }

            if (empty($maps[$class][$field])) {
                continue;
            }

            $this->setDictionary($field, $maps[$class][$field]);
        }
    }
}
