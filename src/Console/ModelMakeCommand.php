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
        return __DIR__ . '/stubs/model.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return "{$rootNamespace}\Models";
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $table      = Str::snake(Str::pluralStudly($this->argument('name')));
        $indent     = str_repeat(' ', 8);
        $timestamps = 'public $timestamps = false;';
        $columns    = [];

        foreach (Schema::getColumnListing($table) as $column) {
            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $column === 'updated_at' && $timestamps = '';
            } else {
                $columns[] = "{$indent}'{$column}',";
            }
        }

        $replace = [
            'DummyColumns'    => implode("\r", $columns),
            'DummyTable'      => $table,
            'DummyTimestamps' => $timestamps,
        ];
        $replace = $this->buildClassParents($replace, Model::class, [
            \App\Models\Model::class,
            \QT\Foundation\Model::class,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }
}
