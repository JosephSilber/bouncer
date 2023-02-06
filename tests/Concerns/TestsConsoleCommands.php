<?php

namespace Silber\Bouncer\Tests\Concerns;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Foundation\Application;
use Mockery as m;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

trait TestsConsoleCommands
{
    public function tearDown(): void
    {
        m::close();
    }

    /**
     * Get a prophesy for the laravel application class.
     *
     * @return \Illuminate\Contracts\Foundation\Application
     */
    protected function laravel()
    {
        $laravel = m::mock(Application::class);

        $laravel->shouldReceive('call')->andReturnUsing(function ($callback) {
            list($command, $method) = $callback;

            $command->{$method}();
        });

        $laravel->shouldReceive('make')->andReturnUsing(function ($class, $arguments) {
            if (empty($arguments['input'])) {
                return new $class($arguments['output']);
            }

            return new $class($arguments['input'], $arguments['output']);
        });

        return $laravel;
    }

    /**
     * Get a prophesy for the console output class.
     *
     * @return \Symfony\Component\Console\Output\NullOutput
     */
    protected function output()
    {
        $output = m::mock(NullOutput::class);

        $output->shouldReceive('getVerbosity')->andReturn(NullOutput::VERBOSITY_QUIET);
        $output->shouldReceive('getFormatter')->andReturn(new OutputFormatter);

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

        return function ($output) use ($messages) {
            foreach ($messages as $message) {
                $output
                    ->shouldReceive('writeln')
                    ->withArgs(function ($m) use ($message) {
                        return $m == $message;
                    });
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

        $command->setLaravel($this->laravel());

        $command->run(new ArrayInput($parameters), $output);

        return $output;
    }
}
