<?php

namespace QT\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
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
    protected $name = 'make:graphql-model';

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
        $table   = Str::snake(Str::pluralStudly($this->argument('name')));
        $columns = collect(Schema::getColumnListing($table))
            ->filter(function ($column) {
                return !in_array($column, [
                    'id', 'created_at', 'updated_at', 'deleted_at',
                ]);
            })
            ->map(function ($column) {
                return str_pad('', 8, ' ', STR_PAD_LEFT) . "'{$column}',";
            });

        $replace = ['DummyColumns' => $columns->implode("\r"), 'DummyTable' => $table];
        $replace = $this->buildClassParents($replace, Model::class, [
            \App\Models\Model::class,
            \QT\Foundation\Model::class,
        ]);

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }
}
