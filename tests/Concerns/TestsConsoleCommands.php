<?php

namespace Silber\Bouncer\Tests\Concerns;

use Prophecy\Argument;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
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

        $laravel->make(OutputStyle::class, Argument::type('array'))->will(function ($arguments) {
            list($class, $arguments) = $arguments;

            return new $class($arguments['input'], $arguments['output']);
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
     * Predict the output of a command.
     *
     * @param  string|array  $message
     * @return \Closure
     */
    protected function predictOutputMessage($message)
    {
        $messages = is_array($message) ? $message : [$message];

        return function (ObjectProphecy $output) use ($messages) {
            foreach ($messages as $message) {
                $output->writeln($message, Argument::any())->shouldBeCalled();
            }
        };
    }

    /**
     * Run the given command.
     *
     * @param  \Illuminate\Console\Command  $command
     * @param  \Closure|array|null  $parameters
     * @param  \Closure|null  $outputPredictions
     * @return mixed
     */
    protected function runCommand(Command $command, $parameters = [], $outputPredictions = null)
    {
        $output = $this->output();

        if ($parameters instanceof Closure) {
            $outputPredictions = $parameters;

            $parameters = [];
        }

        if (! is_null($outputPredictions)) {
            $outputPredictions($output);
        }

        $command->setLaravel($this->laravel()->reveal());

        $command->run(new ArrayInput($parameters), $output->reveal());

        return $output;
    }
}
