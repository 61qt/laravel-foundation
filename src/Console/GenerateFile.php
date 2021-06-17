<?php

namespace QT\Foundation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class GenerateFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate-file
        {--tables= : 表名(多个用,分开)}
        {--module= : 模块名}
        {--all :  是否全部文件都生成}
        {--model :  是否生成 model}
        {--type : 是否生成 type}
        {--resolver : 是否生成 resolver}
        {--mutation : 是否生成 mutation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '根据table生成文件';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tables = explode(',', $this->option('tables'));

        if (empty($tables)) {
            return;
        }

        if ($this->option('all')) {
            $this->input->setOption('model', true);
            $this->input->setOption('type', true);
            $this->input->setOption('resolver', true);
            $this->input->setOption('mutation', true);
        }

        foreach ($tables as $table) {
            $model   = Str::ucfirst(Str::camel(Str::singular($table)));
            $columns = Schema::getColumnListing($table);

            if ($this->option('model')) {
                $this->call('make:graphql:model', [
                    'name'     => $model,
                    '--module' => $this->option('module'),
                ]);
            }

            if ($this->option('resolver')) {
                $this->call('make:graphql:resolver', [
                    'name'      => "{$model}Resolver",
                    '--module'  => $this->option('module'),
                    '--rules'   => join(',', $columns),
                ]);
            }

            if ($this->option('type')) {
                $this->call('make:graphql:type', [
                    'name'     => $model,
                    '--module' => $this->option('module'),
                ]);
            }

            if ($this->option('mutation')) {
                $this->call('make:graphql:mutation', [
                    'name'     => "{$model}Mutation",
                    '--module' => $this->option('module'),
                ]);
            }
        }
    }
}
