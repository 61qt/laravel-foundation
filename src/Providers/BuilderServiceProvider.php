<?php

namespace QT\Foundation\Providers;

use Illuminate\Support\Facades\DB;
use QT\Foundation\Exceptions\Error;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class BuilderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * 减量操作,防止最小值小于0出现越界错误
         *
         * @param array $columns
         */
        Builder::macro('safeDecrement', function ($columns) {
            $values = [];
            foreach ($columns as $column => $amount) {
                if (!is_numeric($amount) || $amount <= 0) {
                    continue;
                }

                $values[$column] = DB::raw("IF(`{$column}` >= {$amount}, `{$column}` - {$amount}, 0)");
            }

            if (!empty($values)) {
                /** @var Builder $this */
                $this->update($values);
            }
        });

        /**
         * 按照主键id读取数据，如果数据不存在报错
         *
         * @param int | string $id
         * @param string $errorMessage
         */
        EloquentBuilder::macro('findOrError', function ($id, $errorMessage = '数据不存在') {
            /** @var EloquentBuilder $this */
            $result = $this->find($id);

            if ((is_array($id) && $result->count() !== count($id)) || $result === null) {
                throw new Error('NOT_FOUND', $errorMessage);
            }
            return $result;
        });

        /**
         * 根据条件读取单条数据，如果数据不存在报错
         *
         * @param string $errorMessage
         */
        EloquentBuilder::macro('firstOrError', function ($errorMessage = '数据不存在') {
            /** @var EloquentBuilder $this */
            $model = $this->first();

            if ($model === null) {
                throw new Error('NOT_FOUND', $errorMessage);
            }

            return $model;
        });

        /**
         * 数据是否存在,不存在抛出异常
         *
         * @param string $errorMessage
         */
        EloquentBuilder::macro('existsOrError', function ($errorMessage = '数据不存在') {
            /** @var EloquentBuilder $this */
            $this->existsOr(fn () => throw new Error('NOT_FOUND', $errorMessage));
        });
    }
}
