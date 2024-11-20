<?php

namespace QT\Foundation\Contracts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableCache
{
    private static array $columns  = [];
    private static array $comments = [];

    /**
     * 获取表字段
     *
     * @param string $table
     * @return array
     */
    public static function getColumns(string $table): array
    {
        if (!isset(static::$columns[$table])) {
            static::$columns[$table] = Schema::getColumns($table);
        }

        return static::$columns[$table];
    }

    /**
     * 获取表注释
     *
     * @param string $table
     * @return string
     */
    public static function getComment(string $table): string
    {
        if (!isset(static::$comments[$table])) {
            $comments = DB::selectOne(
                sprintf(
                    'SELECT `table_comment` FROM `information_schema`.`tables` WHERE `table_schema` = \'%s\' AND `table_name` = \'%s\'',
                    env('DB_DATABASE'),
                    $table
                )
            );

            // 处理表名最后的`表`字
            $tableName = array_change_key_case((array) $comments)['table_comment'] ?? '';
            if (str_ends_with($tableName, '表')) {
                $tableName = rtrim($tableName, '表');
            }
            static::$comments[$table] = $tableName;
        }

        return static::$comments[$table];
    }
}
