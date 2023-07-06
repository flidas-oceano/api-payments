<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GitPullAndCleanLaravelLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:pull-clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta un git pull y limpia los logs de laravel (storage/logs/laravel.logs)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Ejecutar git pull
        $output = shell_exec('git pull');
        $this->info($output);

        // Limpiar el archivo laravel.log
        file_put_contents(storage_path('logs/laravel.log'), '');

        $this->info('laravel.log se vacio.');
    }
}