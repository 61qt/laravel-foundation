<?php

namespace QT\Foundation\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use QT\Foundation\Traits\GeneratorModuleHelper;
use Symfony\Component\Console\Input\InputArgument;

class ImportTaskMakeCommand extends GeneratorCommand
{
    use GeneratorModuleHelper;

    protected $name = 'make:task:import';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/import-task.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return "{$rootNamespace}\Tasks\Import";
    }

    /**
     * Get the import task arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, '异步任务名称.'],
        ];
    }
}
