<?php

namespace QT\Foundation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use QT\Foundation\Traits\Exportable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * 通用model
 * 
 * @package QT\Foundation
 */
class Model extends EloquentModel
{
    use Exportable;

    const NO  = 0;
    const YES = 1;

    public static $yesOrNo = [
        self::NO  => '否',
        self::YES => '是',
    ];

    const STATUS_NORMAL = 0;
    const STATUS_BAN    = 1;

    public static $statusMaps = [
        self::STATUS_NORMAL => '正常',
        self::STATUS_BAN    => '禁用',
    ];

    /**
     * @return array
     */
    protected static function getDefaultDict(): array
    {
        return static::$yesOrNo;
    }

    /**
     * 过滤非法字段
     *
     * @param @attributes
     * @return array
     */
    public static function filterAttributes($attributes)
    {
        return app(static::class)->fill($attributes)->getAttributes();
    }

    /**
     * 批量插入,防止占位符溢出
     *
     * @param $records
     * @param $limit
     * @return void
     */
    public static function safeInsert($records, $limit = 500)
    {
        if ($records instanceof Collection) {
            $records = $records->toArray();
        }

        $offset = 0;
        while (true) {
            $data = array_slice($records, $offset, $limit);

            if (empty($data)) {
                break;
            }

            static::insert($data);

            $offset += $limit;
        }
    }

    /**
     * 查询单个model,支持失败重试机制
     *
     * @param $id
     * @param $tries
     * @param $sleep
     * @return Model|null
     */
    public static function tryFind($id, $tries = 3, $sleep = 0.5)
    {
        return self::tryQuery(self::whereKey($id), $tries, 'first', $sleep);
    }

    /**
     * 执行sql,支持失败重试机制
     *
     * @param $id
     * @param $tries
     * @param $method
     * @param $sleep
     * @return Collection|Model|null
     */
    public static function tryQuery($query, $tries = 3, $method = 'get', $sleep = 0.5)
    {
        while ($tries-- > 0) {
            $result = $query->{$method}();

            if ($result instanceof Collection) {
                if ($result->isNotEmpty()) {
                    return $result;
                }
            } elseif ($result !== null) {
                return $result;
            }

            // 等待db主从同步,微秒级暂停
            usleep($sleep * 1000000);
        }
    }

    /**
     * model支持直接执行safeDecrement
     *
     * @param array $columns
     * @return void
     */
    public function safeDecrement($columns)
    {
        $query = $this->newQuery();

        if (!$this->exists) {
            return $query->safeDecrement($columns);
        }

        foreach ($columns as $column => $count) {
            $this->{$column} = $this->{$column}-$count;

            $this->syncOriginalAttribute($column);
        }

        $query->whereKey($this->getKey())->safeDecrement($columns);
    }

    /**
     * Determine whether a value is JSON castable for inbound manipulation.
     *
     * @param  string  $key
     * @return bool
     */
    protected function isJsonCastable($key)
    {
        return $this->hasCast($key, [
            'array', 'json', 'object', 'collection', 'object_unicode', 'json_unicode',
        ]);
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        $type = $this->getCastType($key);

        if (!empty($value) && in_array($type, [
            'object_unicode',
            'json_unicode',
        ])) {
            return $this->fromJson($value);
        } else {
            return parent::castAttribute($key, $value);
        }
    }

    /**
     * Cast the given attribute to JSON.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return string
     */
    protected function castAttributeAsJson($key, $value)
    {
        $type = $this->getCastType($key);

        if ($type === 'object_unicode') {
            $options = JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT;
        } elseif ($type === 'json_unicode') {
            $options = JSON_UNESCAPED_UNICODE;
        } else {
            return parent::castAttributeAsJson($key, $value);
        }

        return json_encode($value, $options);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        return $this->bootMacros(parent::newBaseQueryBuilder());
    }

    /**
     * 初始化query默认携带的macro方法.
     *
     * @param  Builder $query
     * @return Builder
     */
    protected function bootMacros(Builder $query)
    {
        /**
         * 减量操作,防止最小值小于0出现越界错误
         *
         * @param array $columns
         */
        $query->macro('safeDecrement', function ($columns) {
            $update = [];
            foreach ($columns as $column => $amount) {
                if (!is_numeric($amount) || $amount <= 0) {
                    continue;
                }

                $update[$column] = DB::raw("IF(`{$column}` >= {$amount}, `{$column}` - {$amount}, 0)");
            }

            if (!empty($update)) {
                // macro时$this会指向Builder
                $this->update($update);
            }
        });

        return $query;
    }
}