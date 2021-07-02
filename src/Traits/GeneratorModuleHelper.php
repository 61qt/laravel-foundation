<?php

namespace QT\Foundation\Traits;

use Illuminate\Support\Str;
use Doctrine\DBAL\Types\Types;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\InputOption;

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
        Types::INTEGER      => 'Type::int()',
        Types::SMALLINT     => 'Type::int()',
        Types::BOOLEAN      => 'Type::int()',
        Types::STRING       => 'Type::string()',
        Types::TEXT         => 'Type::string()',
        Types::BIGINT       => 'Type::bigint()',
        Types::DATE_MUTABLE => 'Type::timestamp()',
        Types::JSON         => 'Type::json()',
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
     * @param  string  $name
     * @return string
     */
    protected function getModule()
    {
        if ($this->module !== null) {
            return $this->module;
        }

        return $this->module = Str::ucfirst(Str::camel($this->option('module')));
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
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
     * @param  array  $replace
     * @param  string $table
     * @return array
     */
    protected function buildDataStructureReplacements(array $replace, $table)
    {
        // 获取表字段以及字段
        $dataStructure = [];
        foreach (Schema::getColumnListing($table) as $column) {
            $column      = Schema::getConnection()->getDoctrineColumn($table, $column);
            $columnType  = $column->getType()->getName();
            $description = $column->getComment() ?: $column->getName();

            if (empty($this->typeMaps[$columnType])) {
                continue;
            }

            $dataStructure[] = str_replace(
                ['Column', 'Type', 'Description'],
                [$column->getName(), $this->typeMaps[$columnType], $description],
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
     * @param  array  $replace
     * @param  string $table
     * @return array
     */
    protected function buildClassParents(array $replace, string $mustImplement, array $parents)
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
}
