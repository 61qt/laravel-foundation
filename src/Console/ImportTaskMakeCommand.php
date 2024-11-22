<?php

namespace QT\Foundation\Console;

use QT\Import\Task;
use Illuminate\Console\GeneratorCommand;
use QT\Foundation\Traits\GeneratorModuleHelper;
use Symfony\Component\Console\Input\InputArgument;

class ImportTaskMakeCommand extends GeneratorCommand
{
    use GeneratorModuleHelper;

    protected $name = 'make:import-task';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/import_task.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
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

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $replace = $this->buildClassParents([], Task::class, [
            \App\Tasks\Import\ImportTask::class,
            Task::class,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            parent::buildClass($name)
        );
    }
}
