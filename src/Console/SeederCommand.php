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
        $this->seeders = collect(config('seeders.seeders'))->map(fn ($item, $key) => ! is_int($key) ? $key : $item)->values()->all();

        $seeders = collect($this->seeders)->map(fn ($item) => "`$item`")->implode(', ');

        $this->signature = $this->signature."{seed? : The data seed, you can choice $seeders}";

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
                $this->seeders,
                null,
                null,
                true
            ) : [$seed];

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                sapi_windows_cp_set(65001);
            }

            foreach ($seeds as $seed) {
                $class = $this->seeders[array_flip($this->seeders)[$seed]];

                $this->components->task($class, function () use ($class, $force) {
                    $this->call('db:seed', ['--class' => $class, '--force' => $force]);
                });
            }
        }
    }
}
