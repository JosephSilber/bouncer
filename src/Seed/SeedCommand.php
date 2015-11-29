<?php

namespace Silber\Bouncer\Seed;

use Illuminate\Console\Command;

class SeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bouncer:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the registered bouncer seeders.';

    /**
     * The bouncer seeder instance.
     *
     * @var \Silber\Bouncer\Seeder
     */
    protected $seeder;

    /**
     * Constructor.
     *
     * @param \Silber\Bouncer\Seed\Seeder  $seeder
     */
    public function __construct(Seeder $seeder)
    {
        $this->seeder = $seeder;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $count = $this->seeder->count();

        if ($count == 0) {
            return $this->line('No bouncer seeders have been registered.');
        }

        $this->seeder->run();

        $this->info($this->getSeededMessage($count));
    }

    /**
     * Get the message to display after a successful seeding operation.
     *
     * @param  int  $count
     * @return string
     */
    protected function getSeededMessage($count)
    {
        if ($count == 1) {
            return 'Bouncer successfully seeded.';
        }

        return $count .' seeders have been successfully seeded.';
    }
}
