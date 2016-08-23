<?php

namespace Silber\Bouncer;

use Illuminate\Console\Command;

class UpgradeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bouncer:upgrade {--no-migrate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upgrade from Bouncer < 1.0';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $source = realpath(__DIR__.'/../migrations/upgrade_to_bouncer_1_dot_0.php');

        $file = date('Y_m_d_His').'_upgrade_to_bouncer_1_dot_0.php';

        $target = $this->laravel->databasePath().'/migrations/'.$file;

        copy($source, $target);

        $this->line("<info>Created Migration:</info> {$file}");

        if (! $this->input->getOption('no-migrate')) {
            $this->call('migrate');
        }
    }
}
