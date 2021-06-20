<?php

namespace QT\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;

class TaskTableCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:task-table {--table=async_tasks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建异步任务使用的数据库表结构';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new failed queue jobs table command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer    $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files    = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $table = $this->option('table');
        $model = Str::studly($table);

        $this->replaceMigration(
            $this->createBaseMigration($table), $table, $model
        );

        $this->info('Migration created successfully!');
        // 生成model
        $this->call('make:graphql-model', ['name' => $model]);

        $this->composer->dumpAutoloads();
    }

    /**
     * Create a base migration file for the table.
     *
     * @param  string  $table
     * @return string
     */
    protected function createBaseMigration($table = 'failed_jobs')
    {
        return $this->laravel['migration.creator']->create(
            'create_' . $table . '_table', $this->laravel->databasePath() . '/migrations'
        );
    }

    /**
     * Replace the generated migration with the failed job table stub.
     *
     * @param  string  $path
     * @param  string  $table
     * @param  string  $tableClassName
     * @return void
     */
    protected function replaceMigration($path, $table, $tableClassName)
    {
        $stub = str_replace(
            ['{{table}}', '{{tableClassName}}'],
            [$table, $tableClassName],
            $this->files->get(__DIR__ . '/stubs/task_table.stub')
        );

        $this->files->put($path, $stub);
    }
}
