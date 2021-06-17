<?php

namespace QT\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use QT\Foundation\Traits\GeneratorModuleHelper;
use Illuminate\Foundation\Console\ModelMakeCommand as BaseModelMakeCommand;

/**
 * Laravel Model 生成脚本
 * 
 * @package QT\Foundation\Console
 */
class ModelMakeCommand extends BaseModelMakeCommand
{
    use GeneratorModuleHelper;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:graphql:model';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/model.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return "{$rootNamespace}\Models";
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub    = parent::buildClass($name);
        $table   = Str::plural(Str::snake(class_basename($this->argument('name'))));
        $columns = collect(Schema::getColumnListing($table))
            ->filter(function ($column) {
                return !in_array($column, [
                    'id', 'created_at', 'updated_at', 'deleted_at',
                ]);
            })
            ->map(function ($column) {
                return str_pad('', 8, ' ', STR_PAD_LEFT) . "'{$column}',";
            });

        return str_replace('DummyColumns', $columns->implode("\r"), $stub);
    }
}
