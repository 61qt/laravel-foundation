<?php

namespace QT\Foundation\Console;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Doctrine\DBAL\Types\Types;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * GraphQL Resolver 生成脚本
 * 
 * @package QT\Foundation\Console
 */
class ResolverMakeCommand extends GeneratorCommand
{
    use Traits\GeneratorModuleHelper {
        Traits\GeneratorModuleHelper::getOptions as getModuleOptions;
    }

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:graphql:resolver';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resolver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建Graphql Resolver';

    /**
     * 数据库字段类型在validate校验规则上的映射
     * 
     * @var array
     */
    protected $ruleMaps = [
        'integer'  => 'int',
        'smallint' => 'int',
        'string'   => 'string',
        'text'     => 'string',
        'bigint'   => 'int',
        'datetime' => 'date',
    ];

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'stubs/resolver.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return "{$rootNamespace}\Resolver";
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $rules   = $this->option('rules');
        $model   = $this->option('model');
        $replace = [];

        // 根据Resolver的名称获取Model名
        if ($model === null) {
            $class = str_replace($this->getNamespace($name).'\\', '', $name);
            $model = str_replace('Resolver', '', $class);
        }

        $table   = Str::plural(Str::snake($model));
        $replace = $this->buildModelReplacements($replace, $model);
        $replace = $this->buildRulesReplacements($replace, $table, explode(',', $rules));

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Build the model replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildModelReplacements(array $replace, $model)
    {
        $modelClass = $this->parseModel($model);

        if (! class_exists($modelClass)) {
            if ($this->confirm("A {$modelClass} model does not exist. Do you want to generate it?", true)) {
                $this->call('make:model', ['name' => $model, '--module' => $this->option('module')]);
            }
        }

        return array_merge($replace, [
            'DummyFullModelClass' => $modelClass,
            'DummyModelClass'     => class_basename($modelClass),
            'DummyModelVariable'  => lcfirst(class_basename($modelClass)),
        ]);
    }

    /**
     * Build the rules replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildRulesReplacements(array $replace, $table, $ruleColumns)
    {
        if (empty($ruleColumns)) {
            return $replace;
        }

        $columns = [];
        // 获取表字段以及字段
        foreach (Schema::getColumnListing($table) as $column) {
            $columns[$column] = Schema::getConnection()->getDoctrineColumn($table, $column);
        }

        $rules = [];
        foreach ($ruleColumns as $ruleColumn) {
            if (empty($columns[$ruleColumn])) {
                continue;
            }

            $rule   = [];
            $column = $columns[$ruleColumn];

            if ($column->getNotnull()) {
                $rule[] = 'required';
            }

            if (array_key_exists($column->getType()->getName(), $this->ruleMaps)) {
                $rule[] = $this->ruleMaps[$column->getType()->getName()];
            }

            if ($column->getType()->getName() === Types::STRING) {
                $rule[] = "max:{$column->getLength()}";
            }

            if (Str::contains($column->getName(), ['is_', 'status'])) {
                $rule[] = "in_dict:{$table}";
            } elseif ($column->getUnsigned()) {
                $rule[] = "min:0";
            }

            $rule    = implode('|', $rule);
            $rules[] = str_pad('', 8, ' ', STR_PAD_LEFT) . "'{$ruleColumn}' => '{$rule}',";
        }

        return array_merge($replace, ['DummyRules' => implode("\n", $rules)]);
    }

    /**
     * Get the fully-qualified model class name.
     *
     * @param  string  $model
     * @return string
     */
    protected function parseModel($model)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        $model         = trim(str_replace('/', '\\', $model), '\\');
        $rootNamespace = $this->rootNamespace().'Models\\';

        if (! Str::startsWith($model, $rootNamespace)) {
            $model = $rootNamespace.$model;
        }

        return $model;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array_merge($this->getModuleOptions(), [
            ['rules', null, InputOption::VALUE_OPTIONAL, 'rules'],

            ['model', null, InputOption::VALUE_OPTIONAL, 'model']
        ]);
    }
}
