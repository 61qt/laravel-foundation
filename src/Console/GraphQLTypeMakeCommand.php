<?php

namespace QT\Foundation\Console;

use Illuminate\Support\Str;
use InvalidArgumentException;
use QT\GraphQL\Definition\ModelType;
use Illuminate\Support\Facades\Schema;
use QT\Foundation\Contracts\TableCache;
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
        'boolean'   => 'int',
        'int'       => 'int',
        'smallint'  => 'int',
        'mediumint' => 'int',
        'tinyint'   => 'int',
        'bigint'    => 'bigint',
        'varchar'   => 'string',
        'char'      => 'string',
        'year'      => 'string',
        'string'    => 'string',
        'timestamp' => 'timestamp',
        'datetime'  => 'timestamp',
    ];

    /** @var array */
    protected $likeFields = [
        'name',
        'title',
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
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return "{$rootNamespace}\GraphQL\Type";
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
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
        $replace = $this->buildTableCommentReplacements($replace, $table, 'DummyDescription');
        $replace = $this->buildClassParents($replace, ModelType::class, [
            \App\GraphQL\Type\ModelType::class,
            \App\GraphQL\Definition\ModelType::class,
            \QT\Foundation\GraphQL\Definition\ModelType::class,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }

    /**
     * Build the resolver replacement values.
     *
     * @param array $replace
     * @param string $type
     * @return array
     */
    protected function buildResolverReplacements(array $replace, string $type): array
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
     * @param array $replace
     * @param string $table
     * @return array
     */
    protected function buildFilterReplacements(array $replace, string $table): array
    {
        // 根据索引生成默认筛选条件
        $indexColumns = [];
        foreach (Schema::getIndexes($table) as $index) {
            foreach ($index['columns'] as $column) {
                $indexColumns[$column] = true;
            }
        }

        $filters = [];
        // 获取表字段以及字段
        foreach (TableCache::getColumns($table) as $column) {
            $operators = null;
            $type      = $column['type_name'];
            $name      = $column['name'];

            if ($type === 'tinyint') {
                $operators = "['=', 'in', '!=']";
            } elseif (in_array($type, ['varchar', 'char', 'string'])) {
                if (Str::contains($name, $this->likeFields)) {
                    $operators = "['like', '=']";
                }
            }
            if (isset($indexColumns[$column['name']]) && !empty($this->filterMaps[$type])) {
                $operators = "['=', 'in']";
            }

            if (!empty($operators)) {
                $filters[$name] = $this->buildFilterString($name, $type, $operators);
            }
        }

        if (empty($filters)) {
            $filters = '';
        } else {
            $filters = sprintf(
                "%s\$registrar\n%s;",
                str_repeat(' ', 8),
                implode("\n", $filters),
            );
        }

        return array_merge($replace, ['DummyFilters' => $filters]);
    }

    /**
     * 构建筛选条件
     *
     * @param string $name
     * @param string $type
     * @param string $operators
     * @return string
     */
    protected function buildFilterString(string $name, string $type, string $operators): string
    {
        $method = $this->filterMaps[$type] ?? 'string';
        $filter = "->{$method}('{$name}', {$operators})";

        return str_repeat(' ', 12) . $filter;
    }

    /**
     * Get the fully-qualified resolver class name.
     *
     * @param string $type
     * @return string
     */
    protected function parseType(string $type): string
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
