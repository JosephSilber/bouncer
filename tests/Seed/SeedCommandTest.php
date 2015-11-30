<?php

use Silber\Bouncer\Seed\Seeder;
use Silber\Bouncer\Seed\SeedCommand;

use Prophecy\Argument;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Console\Formatter\OutputFormatter;

class SeedCommandTest extends PHPUnit_Framework_TestCase
{
    public function test_seed_command_outputs_proper_message_when_there_are_no_seeders()
    {
        $seeder = $this->seeder();
        $seeder->count()->willReturn(0)->shouldBeCalled();

        $output = $this->output();
        $output->writeln('No bouncer seeders have been registered.', Argument::any())->shouldBeCalled();

        $this->seed($seeder->reveal(), $output->reveal());
    }

    public function test_seed_command_runs_seeder_and_outputs_proper_message_for_single_seeder_callback()
    {
        $seeder = $this->seeder();
        $seeder->count()->willReturn(1)->shouldBeCalled();
        $seeder->run()->shouldBeCalled();

        $output = $this->output();
        $output->writeln('<info>Bouncer successfully seeded.</info>', Argument::any())->shouldBeCalled();

        $this->seed($seeder->reveal(), $output->reveal());
    }

    public function test_seed_command_runs_seeder_and_outputs_proper_message_for_multiple_seeder_callbacks()
    {
        $seeder = $this->seeder();
        $seeder->count()->willReturn(4)->shouldBeCalled();
        $seeder->run()->shouldBeCalled();

        $output = $this->output();
        $output->writeln('<info>4 seeders have been successfully seeded.</info>', Argument::any())->shouldBeCalled();

        $this->seed($seeder->reveal(), $output->reveal());
    }

    /**
     * Run the seed command with the given seeder and output objects.
     *
     * @param  \Silber\Bouncer\Seed\Seeder  $seeder
     * @param  \Symfony\Component\Console\Output\NullOutput  $output
     * @return mixed
     */
    protected function seed(Seeder $seeder, NullOutput $output)
    {
        $command = new SeedCommand($seeder);

        $command->setLaravel($this->laravel()->reveal());

        return $command->run(new ArrayInput([]), $output);
    }

    /**
     * Get a prophesy for the laravel application class.
     *
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function laravel()
    {
        $laravel = $this->prophesize(Application::class);

        $laravel->call(Argument::type('array'))->will(function ($arguments) {
            list($command, $method) = $arguments[0];

            $command->{$method}();
        });

        return $laravel;
    }

    /**
     * Get a prophesy for the console output class.
     *
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function output()
    {
        $output = $this->prophesize(NullOutput::class);

        $output->getVerbosity()->willReturn(NullOutput::VERBOSITY_QUIET);
        $output->getFormatter()->willReturn(new OutputFormatter);

        return $output;
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
