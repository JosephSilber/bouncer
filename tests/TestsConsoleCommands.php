<?php

use Prophecy\Argument;
use Illuminate\Console\Command;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Console\Formatter\OutputFormatter;

trait TestsConsoleCommands
{
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

    protected function predictOutputMessage($message)
    {
        return function (ObjectProphecy $output) use ($message) {
            $output->writeln($message, Argument::any())->shouldBeCalled();
        };
    }

    /**
     * Run the given command.
     *
     * @param  \Illuminate\Console\Command  $command
     * @return mixed
     */
    protected function runCommand(Command $command, Closure $outputPredictions)
    {
        $output = $this->output();

        $outputPredictions($output);

        $command->setLaravel($this->laravel()->reveal());

        $command->run(new ArrayInput([]), $output->reveal());

        return $output;
    }
}
