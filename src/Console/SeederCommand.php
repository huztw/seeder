<?php

namespace Huztw\Seeder\Console;

use Illuminate\Console\Command;

class SeederCommand extends Command
{
    /**
     * Register seeders
     *
     * @var array
     */
    private $seeders;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:seeder
                            {--A|all : All seeds}
                            {--f|force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the choice Seeder';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->seeders = config('seeders.seeders');

        $seeders = collect($this->seeders)->map(function ($item, $key) {return "`$key`";})->implode(', ');

        $this->signature = $this->signature . "{seed? : The data seed, you can choice $seeders}";

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->components->info('Preparing seeder.');

        $force = $this->option('force');

        if ($this->option('all')) {
            foreach ($this->seeders as $class) {
                $this->components->task($class, function () use ($class, $force) {
                    $this->call('db:seed', ['--class' => $class, '--force' => $force]);
                });
            }
        } else {
            $seed = $this->argument('seed');

            $seeds = $seed === null ? $this->choice(
                'What Seed should run?',
                array_keys($this->seeders),
                null,
                null,
                true
            ) : [$seed];

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                sapi_windows_cp_set(65001);
            }

            foreach ($seeds as $seed) {
                $this->components->task($this->seeders[$seed], function () use ($seed, $force) {
                    $this->call('db:seed', ['--class' => $this->seeders[$seed], '--force' => $force]);
                });
            }
        }
    }
}
