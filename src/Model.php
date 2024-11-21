<?php

namespace QT\Foundation;

use QT\GraphQL\Relations\HasOne;
use QT\GraphQL\Relations\HasMany;
use Illuminate\Support\Collection;
use QT\Foundation\Traits\Exportable;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * 通用model
 *
 * @method static static doesntExistOrError($errorMessage = '')
 * @method static static existsOrError($errorMessage = '')
 * @method static static|Collection find($id, $columns = ['*'])
 * @method static Collection findMany($id, $columns = ['*'])
 * @method static static|Collection findOrError($id, $errorMessage = '', $columns = ['*'])
 * @method static static|Collection findOrFail($id, $columns = ['*'])
 * @method static static findOrNew($id, $columns = ['*'])
 * @method static static first($columns = ['*'])
 * @method static static firstOr($columns = ['*'], Closure $callback = null)
 * @method static static firstOrCreate(array $attributes, array $values = [])
 * @method static static firstOrError($errorMessage = '', $columns = ['*'])
 * @method static static firstOrFail($columns = ['*'])
 * @method static static firstOrNew(array $attributes, array $values = [])
 * @method static static updateOrCreate(array $attributes, array $values = [])
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
     * @param array|Collection $attributes
     * @return array
     */
    public static function filterAttributes(array|Collection $attributes): array
    {
        return app(static::class)->fill($attributes)->getAttributes();
    }

    /**
     * 批量插入,防止占位符溢出
     *
     * @param array|Collection $records
     * @param int $limit
     * @return void
     */
    public static function safeInsert(array|Collection $records, int $limit = 500)
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
     * @param float|int $sleep
     * @param string $method
     * @return Collection|Model|null
     */
    public static function delayQuery($query, float|int $sleep = 0.5, string $method = 'get')
    {
        // 等待db主从同步,微秒级暂停
        usleep($sleep * 1000000);

        return $query->{$method}();
    }

    /**
     * 查询单个model,支持失败重试机制
     *
     * @param int $id
     * @param int $tries
     * @param float|int $sleep
     * @return Model|null
     */
    public static function tryFind(int $id, int $tries = 3, float|int $sleep = 0.5)
    {
        return self::tryQuery(self::whereKey($id), $tries, 'first', $sleep);
    }

    /**
     * 执行sql,支持失败重试机制
     *
     * @param $query
     * @param int $tries
     * @param string $method
     * @param float|int $sleep
     * @return Collection|Model|null
     */
    public static function tryQuery($query, int $tries = 3, string $method = 'get', float|int $sleep = 0.5)
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
     * @param $baseQuery
     * @param int $lastId
     * @param int $limit
     * @param string $orderBy
     * @return \Generator
     */
    public static function getCursor($baseQuery, int $lastId = 0, int $limit = 1000, string $orderBy = 'asc')
    {
        $table    = $baseQuery->getModel()->getTable();
        $keyName  = $baseQuery->getModel()->getKeyName();
        $operator = $orderBy === 'asc' ? '>' : '<';
        // 数据量太大，会连接导致超时
        $resultCount    = 0;
        $reconnectLimit = 1000000;

        while (true) {
            if ($resultCount >= $reconnectLimit) {
                $resultCount = 0;
                $baseQuery->getConnection()->reconnect();
            }
            $models = (clone $baseQuery)
                ->when($lastId > 0, function ($query) use ($table, $keyName, $operator, $lastId) {
                    $query->where("{$table}.{$keyName}", $operator, $lastId);
                })
                ->orderBy("{$table}.{$keyName}", $orderBy)
                ->limit($limit)
                ->get();

            foreach ($models as $model) {
                $lastId = $model->{$keyName};
                yield $model;
            }

            if ($models->count() !== $limit) {
                break;
            }
        }
    }

    /**
     * model支持直接执行safeDecrement
     *
     * @param array $columns
     * @return void
     */
    public function safeDecrement(array $columns)
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
     * 定义一对一关系（含额外key）
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @param array $extraKeys
     * @return HasOne
     */
    public function hasOneByExtra($related, $foreignKey = null, $localKey = null, array $extraKeys = [])
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasOne(
            $instance->newQuery(),
            $this,
            $instance->getTable() . '.' . $foreignKey,
            $localKey,
            $extraKeys
        );
    }

    /**
     * 定义一对多关系（含额外key）
     *
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @param array $extraKeys ['model_key' => 'relation_key']
     * @return HasMany
     */
    public function hasManyByExtra($related, $foreignKey = null, $localKey = null, array $extraKeys = [])
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasMany(
            $instance->newQuery(),
            $this,
            $instance->getTable() . '.' . $foreignKey,
            $localKey,
            $extraKeys
        );
    }
}
