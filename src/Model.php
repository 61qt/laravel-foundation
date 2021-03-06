<?php

namespace QT\Foundation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use QT\Foundation\Traits\Exportable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * @method static Collection        findMany($id, $columns = ['*'])
 * @method static static|Collection find($id, $columns = ['*'])
 * @method static static|Collection findOrFail($id, $columns = ['*'])
 * @method static static|Collection findOrError($id, $errorMessage = '')
 * @method static static            findOrNew($id, $columns = ['*'])
 * @method static static            firstOrNew(array $attributes, array $values = [])
 * @method static static            firstOrCreate(array $attributes, array $values = [])
 * @method static static            updateOrCreate(array $attributes, array $values = [])
 * @method static static            first($columns = ['*'])
 * @method static static            firstOrFail($columns = ['*'])
 * @method static static            firstOr($columns = ['*'], Closure $callback = null)
 * @method static static            firstOrError($errorMessage = '')
 * @method static static            existsOrError($errorMessage = '')
 *
 * 通用model
 *
 * @package QT\Foundation
 */
class Model extends EloquentModel
{
    use Exportable;

    public const NO  = 0;
    public const YES = 1;

    public static $yesOrNo = [
        self::NO  => '否',
        self::YES => '是',
    ];

    public const STATUS_NORMAL = 0;
    public const STATUS_BAN    = 1;

    public static $statusMaps = [
        self::STATUS_NORMAL => '正常',
        self::STATUS_BAN    => '禁用',
    ];

    protected static function boot()
    {
        parent::boot();

        static::eventBoot();

        static::baseModelBoot();
    }

    protected static function eventBoot()
    {
    }

    protected static function baseModelBoot()
    {
    }

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
     * 延迟查询数据
     *
     * @param $query
     * @param $sleep
     * @param $method
     * @return Model|null
     */
    public static function delayQuery($query, $sleep = 0.5, $method = 'get')
    {
        // 等待db主从同步,微秒级暂停
        usleep($sleep * 1000000);

        return $query->{$method}();
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
     * 流式获取
     *
     * @param @baseQuery
     * @param @lastId
     * @param @limit
     * @return \Generator
     */
    public static function getCursor($baseQuery, $lastId = 0, $limit = 1000)
    {
        $table   = $baseQuery->getModel()->getTable();
        $keyName = $baseQuery->getModel()->getKeyName();

        while (true) {
            $models = (clone $baseQuery)
                ->where("{$table}.{$keyName}", '>', $lastId)
                ->orderBy("{$table}.{$keyName}", 'asc')
                ->limit($limit)
                ->get();

            if ($models->isEmpty()) {
                break;
            }

            foreach ($models as $model) {
                yield $model;
            }

            $lastId = $models->last()->{$keyName};
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
            $this->{$column} = $this->{$column} - $count;

            $this->syncOriginalAttribute($column);
        }

        $query->whereKey($this->getKey())->safeDecrement($columns);
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
            $values = [];
            foreach ($columns as $column => $amount) {
                if (!is_numeric($amount) || $amount <= 0) {
                    continue;
                }

                $values[$column] = DB::raw("IF(`{$column}` >= {$amount}, `{$column}` - {$amount}, 0)");
            }

            if (!empty($values)) {
                // macro时$this会指向Builder
                /** @var Builder $this */
                $this->update($values);
            }
        });

        return $query;
    }
}
