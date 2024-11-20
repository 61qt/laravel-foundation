<?php

namespace QT\Foundation\Traits;

use Illuminate\Support\Str;
use QT\Foundation\Contracts\TableCache;
use Symfony\Component\Console\Input\InputOption;

/**
 * 生成module下文件方法集合
 *
 * @package QT\Foundation\Traits
 */
trait GeneratorModuleHelper
{
    /**
     * 模块名称
     *
     * @var string|null
     */
    protected $module = null;

    /**
     * 数据库字段类型在GraphQL类型上的映射
     *
     * @var array
     */
    protected $typeMaps = [
        'boolean'              => 'Type::int()',
        'int'                  => 'Type::int()',
        'smallint'             => 'Type::int()',
        'mediumint'            => 'Type::int()',
        'tinyint'              => 'Type::int()',
        'bigint'               => 'Type::bigint()',
        'float'                => 'Type::float()',
        'smallfloat'           => 'Type::float()',
        'double'               => 'Type::float()',
        'real'                 => 'Type::float()',
        'decimal'              => 'Type::string()',
        'numeric'              => 'Type::string()',
        'guid'                 => 'Type::string()',
        'year'                 => 'Type::string()',
        'text'                 => 'Type::string()',
        'char'                 => 'Type::string()',
        'varchar'              => 'Type::string()',
        'tinytext'             => 'Type::string()',
        'mediumtext'           => 'Type::string()',
        'longtext'             => 'Type::string()',
        'json'                 => 'Type::json()',
        'simple_array'         => 'Type::json()',
        'set'                  => 'Type::json()',
        'date'                 => 'Type::timestamp()',
        'date_immutable'       => 'Type::timestamp()',
        'dateinterval'         => 'Type::timestamp()',
        'datetime'             => 'Type::timestamp()',
        'datetime_immutable'   => 'Type::timestamp()',
        'datetimetz'           => 'Type::timestamp()',
        'datetimetz_immutable' => 'Type::timestamp()',
        'time'                 => 'Type::timestamp()',
        'time_immutable'       => 'Type::timestamp()',
        'timestamp'            => 'Type::timestamp()',
        'binary'               => 'Type::resource()',
        'blob'                 => 'Type::resource()',
        'longblob'             => 'Type::resource()',
        'mediumblob'           => 'Type::resource()',
        'tinyblob'             => 'Type::resource()',
        'varbinary'            => 'Type::resource()',
        'enum'                 => 'Type::mixed()',
    ];

    /**
     * Graphql字段数据结构
     *
     * @var string
     */
    protected $columnDataStructure = <<<STRING
            'Column' => [
                'type'        => Type,
                'description' => 'Description',
            ],
STRING;

    /**
     * Get the written module.
     *
     * @return string
     */
    protected function getModule(): string
    {
        if ($this->module !== null) {
            return $this->module;
        }

        return $this->module = Str::ucfirst(Str::camel($this->option('module')));
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        $module = $this->getModule();

        if (empty($module)) {
            return parent::getPath($name);
        }

        $name = ltrim(Str::replaceFirst($this->rootNamespace(), '', $name), '\\');

        return module_path($module) . '/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        $module = $this->getModule();

        return empty($module) ? parent::rootNamespace() : "Modules\\{$module}\\";
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge(parent::getOptions(), [
            ['module', null, InputOption::VALUE_OPTIONAL, '指定模块'],

            ['parent_class', null, InputOption::VALUE_OPTIONAL, '指定继承父类'],
        ]);
    }

    /**
     * Build the graphql data structure replacement values.
     *
     * @param array $replace
     * @param string $table
     * @param array $ignoreColumns
     * @return array
     */
    protected function buildDataStructureReplacements(array $replace, string $table, array $ignoreColumns = []): array
    {
        // 获取表字段以及字段
        $dataStructure = [];
        foreach (TableCache::getColumns($table) as $column) {
            $columnType  = $column['type_name'];
            $description = $column['comment'] ?: $column['name'];

            if (empty($this->typeMaps[$columnType]) || in_array($column['name'], $ignoreColumns)) {
                continue;
            }

            $dataStructure[] = str_replace(
                ['Column', 'Type', 'Description'],
                [$column['name'], $this->typeMaps[$columnType], $description],
                $this->columnDataStructure
            );
        }

        return array_merge($replace, [
            'DummyDataStructure' => implode("\n", $dataStructure),
        ]);
    }

    /**
     * Build class extends parent
     *
     * @param array $replace
     * @param string $mustImplement
     * @param array $parents
     * @return array
     */
    protected function buildClassParents(array $replace, string $mustImplement, array $parents): array
    {
        $parents = array_merge($this->option('parent_class') ?? [], $parents);

        foreach ($parents as $parent) {
            if (!class_exists($parent)) {
                continue;
            }

            if (!is_subclass_of($parent, $mustImplement) && $parent !== $mustImplement) {
                continue;
            }

            return array_merge($replace, [
                'DummyParentFullName' => $parent,
                'DummyParent'         => class_basename($parent),
            ]);
        }
    }

    /**
     * Build type description
     *
     * @param array $replace
     * @param string $table
     * @param string $replaceKey
     * @return array
     */
    protected function buildTableCommentReplacements(array $replace, string $table, string $replaceKey): array
    {
        $tableName = TableCache::getComment($table);

        return array_merge($replace, [
            $replaceKey => $tableName,
        ]);
    }
}
