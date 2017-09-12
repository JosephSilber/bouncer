<?php

use Silber\Bouncer\Seed\Seeder;
use Silber\Bouncer\Seed\SeedCommand;

use PHPUnit\Framework\TestCase;

class SeedCommandTest extends TestCase
{
    use TestsConsoleCommands;

    public function test_seed_command_outputs_proper_message_when_there_are_no_seeders()
    {
        $seeder = $this->seeder();
        $seeder->count()->willReturn(0)->shouldBeCalled();

        $this->seed(
            $seeder->reveal(),
            $this->predictOutputMessage('No bouncer seeders have been registered.')
        );
    }

    public function test_seed_command_runs_seeder_and_outputs_proper_message_for_single_seeder_callback()
    {
        $seeder = $this->seeder();
        $seeder->count()->willReturn(1)->shouldBeCalled();
        $seeder->run()->shouldBeCalled();

        $this->seed(
            $seeder->reveal(),
            $this->predictOutputMessage('<info>Bouncer successfully seeded.</info>')
        );
    }

    public function test_seed_command_runs_seeder_and_outputs_proper_message_for_multiple_seeder_callbacks()
    {
        $seeder = $this->seeder();
        $seeder->count()->willReturn(4)->shouldBeCalled();
        $seeder->run()->shouldBeCalled();

        $this->seed(
            $seeder->reveal(),
            $this->predictOutputMessage('<info>4 seeders have been successfully seeded.</info>')
        );
    }

    /**
     * Run the seed command with the given seeder.
     *
     * @param  \Silber\Bouncer\Seed\Seeder  $seeder
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function seed(Seeder $seeder, Closure $outputPredictions)
    {
        return $this->runCommand(new SeedCommand($seeder), $outputPredictions);
    }

    /**
     * Get a prophesy for the seeder class.
     *
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function seeder()
    {
        return $this->prophesize(Seeder::class);
    }
}
