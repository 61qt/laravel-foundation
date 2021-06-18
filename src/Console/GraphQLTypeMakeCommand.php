<?php

namespace QT\Foundation\Console;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\GeneratorCommand;
use QT\Foundation\Traits\GeneratorModuleHelper;

/**
 * GraphQL type 生成脚本
 * 
 * @package QT\Foundation\Console
 */
class GraphQLTypeMakeCommand extends GeneratorCommand
{
    use GeneratorModuleHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:graphql:type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成Graphql Type';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'GraphQl Type';

    /**
     * 数据库类型跟筛选类型的映射
     * 
     * @var array
     */
    protected $filterMaps = [
        'integer'  => 'int',
        'smallint' => 'int',
        'bigint'   => 'int',
        'string'   => 'string',
        'text'     => 'string',
    ];

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/type.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return "{$rootNamespace}\GraphQL\Type";
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $type  = str_replace($this->getNamespace($name).'\\', '', $name);
        $table = Str::plural(Str::snake($type));

        $replace = ['DummyObjectName' => lcfirst($type), 'DummyDescription' => $type];
        $replace = $this->buildResolverReplacements($replace, $type);
        $replace = $this->buildFilterReplacements($replace, $type);
        $replace = $this->buildDataStructureReplacements($replace, $table);

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    /**
     * Build the resolver replacement values.
     *
     * @param  array  $replace
     * @param  string $type
     * @return array
     */
    protected function buildResolverReplacements(array $replace, $type)
    {
        $resolverClass = $this->parseType($type);

        if (! class_exists($resolverClass)) {
            if ($this->confirm("{$resolverClass} 不存在. 是否要生成?", true)) {
                $this->call('make:graphql:resolver', ['name' => "{$type}Resolver", '--module' => $this->option('module')]);
            }
        }

        return array_merge($replace, [
            'DummyResolverClass' => $resolverClass,
            'DummyResolver'      => class_basename($resolverClass),
        ]);
    }

    /**
     * Build the rules replacement values.
     *
     * @param  array  $replace
     * @return array
     */
    protected function buildFilterReplacements(array $replace, $table)
    {
        $schema  = Schema::getConnection()->getDoctrineSchemaManager();
        $columns = [];
        foreach ($schema->listTableIndexes($table) as $index) {
            $columns = array_merge($columns, $index->getColumns());
        }

        $filters = [];
        $columns = collect(array_unique($columns));
        foreach ($columns as $column) {
            $column = Schema::getConnection()->getDoctrineColumn($table, $column);
    
            $type = $column->getType()->getName();
    
            if (empty($this->filterMaps[$type])) {
                continue;
            }
    
            $name   = $column->getName();
            $method = $this->filterMaps[$type];
            $filter = "->{$method}('{$name}', ['=', 'in'])";

            $filters[] = str_pad('', 12, ' ', STR_PAD_LEFT).$filter;
        }

        if (empty($filters)) {
            $filters = '';
        } else {
            $filters = sprintf(
                "%s\$registrar\n%s;",
                str_pad('', 8, ' ', STR_PAD_LEFT),
                implode("\n", $filters),
            );
        }

        return array_merge($replace, ['DummyFilters' => $filters]);
    }

    /**
     * Get the fully-qualified resolver class name.
     *
     * @param  string  $type
     * @return string
     */
    protected function parseType($type)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $type)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        $resolver    = trim(str_replace('/', '\\', $type), '\\').'Resolver';
        $rootNamespace = $this->rootNamespace().'Resolvers\\';

        if (! Str::startsWith($resolver, $rootNamespace)) {
            $resolver = $rootNamespace.$resolver;
        }

        return $resolver;
    }
}
