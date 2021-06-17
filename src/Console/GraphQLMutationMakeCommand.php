<?php

namespace QT\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use QT\Foundation\Traits\GeneratorModuleHelper;

/**
 * GraphQL mutation 生成脚本
 * 
 * @package QT\Foundation\Console
 */
class GraphQLMutationMakeCommand extends GeneratorCommand
{
    use GeneratorModuleHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:graphql:mutation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成Graphql Mutation';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'GraphQl Mutation';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/mutation.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return "{$rootNamespace}\GraphQL\Mutation";
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        // 根据Mutation的名称获取Model名
        $class     = str_replace($this->getNamespace($name) . '\\', '', $name);
        $type      = str_replace('Mutation', '', $class);
        $table     = Str::plural(Str::snake($type));
        $typeClass = $this->rootNamespace($name) . 'GraphQL\\Type\\' . $type;

        if (!class_exists($typeClass)) {
            if ($this->confirm("{$typeClass} 不存在. 是否要生成?", true)) {
                $this->call('make:graphql:type', ['name' => $type, '--module' => $this->option('module')]);
            }
        }

        $replace = $this->buildDataStructureReplacements([
            'DummyObjectName'  => lcfirst($type),
            'DummyDescription' => $type,
            'DummyTypeName'    => $type,
        ], $table);

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }
}
