<?php

namespace QT\Foundation\Console;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Doctrine\DBAL\Types\Types;
use QT\GraphQL\Definition\ModelType;
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
    protected $name = 'make:graphql-type';

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
        Types::INTEGER      => 'int',
        Types::SMALLINT     => 'int',
        Types::BOOLEAN      => 'int',
        Types::BIGINT       => 'bigint',
        Types::STRING       => 'string',
        Types::TEXT         => 'string',
        Types::ASCII_STRING => 'string',
    ];

    /**
     * @var array
     */
    protected $likeFields = [
        'name',
    ];

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/type.stub';
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
        $type  = str_replace($this->getNamespace($name) . '\\', '', $name);
        $table = Str::snake(Str::pluralStudly($type));

        $replace = ['DummyObjectName' => lcfirst($type), 'DummyDescription' => $type];
        $replace = $this->buildResolverReplacements($replace, $type);
        $replace = $this->buildFilterReplacements($replace, $table);
        $replace = $this->buildDataStructureReplacements($replace, $table);
        $replace = $this->buildClassParents($replace, ModelType::class, [
            \App\GraphQL\Type\ModelType::class,
            \App\GraphQL\Definition\ModelType::class,
            \QT\Foundation\GraphQL\Definition\ModelType::class,
        ]);

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

        if (!class_exists($resolverClass)) {
            if ($this->confirm("{$resolverClass} 不存在. 是否要生成?", true)) {
                $this->call('make:graphql-resolver', ['name' => "{$type}Resolver", '--module' => $this->option('module')]);
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
        $schema = Schema::getConnection()->getDoctrineSchemaManager();
        $table  = $schema->listTableDetails($table);

        $func = function ($filters, $column, $type, $operators) {
            $name   = $column->getName();
            $method = $this->filterMaps[$type];
            $filter = "->{$method}('{$name}', {$operators})";

            $filters[$name] = str_pad('', 12, ' ', STR_PAD_LEFT) . $filter;
        };

        // 根据字典生成默认筛选条件
        $filters = collect();
        foreach ($table->getColumns() as $column) {
            $operators = null;
            $type      = $column->getType()->getName();
            if ($type === Types::BOOLEAN) {
                $operators = "['=', 'in', '!=']";
            }

            foreach ($this->likeFields as $field) {
                if (Str::contains($column->getName(), $field)) {
                    $operators = "['like']";
                }
            }

            if (empty($operators)) {
                continue;
            }

            $func($filters, $column, $type, $operators);
        }

        // 根据索引生成默认筛选条件
        foreach ($table->getIndexes() as $index) {
            foreach ($index->getColumns() as $column) {
                $column = $table->getColumn($column);
                $type   = $column->getType()->getName();

                if (empty($this->filterMaps[$type])) {
                    continue;
                }

                $func($filters, $column, $type, "['=', 'in']");
            }
        }

        if (empty($filters)) {
            $filters = '';
        } else {
            $filters = sprintf(
                "%s\$registrar\n%s;",
                str_pad('', 8, ' ', STR_PAD_LEFT),
                $filters->implode("\n"),
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

        $resolver      = trim(str_replace('/', '\\', $type), '\\') . 'Resolver';
        $rootNamespace = $this->rootNamespace() . 'Resolvers\\';

        if (!Str::startsWith($resolver, $rootNamespace)) {
            $resolver = $rootNamespace . $resolver;
        }

        return $resolver;
    }
}
