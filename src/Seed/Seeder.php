<?php

namespace Silber\Bouncer\Seed;

use Closure;
use Illuminate\Container\Container;

class Seeder
{
    /**
     * List of registered seeders.
     *
     * @var array
     */
    protected $seeders = [];

    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param \Illuminate\Container\Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register the given seeder.
     *
     * @param  \Closure|string  $seeder
     * @return $this
     */
    public function register($seeder)
    {
        $this->seeders[] = $seeder;

        return $this;
    }

    /**
     * Run the registered seeders.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->seeders as $seeder) {
            $this->call($seeder);
        }
    }

    /**
     * Get the number of registered seeders.
     *
     * @return int
     */
    public function count()
    {
        return count($this->seeders);
    }

    /**
     * Call the given seeder.
     *
     * @param  \Closure|string  $seeder
     * @return void
     */
    protected function call($seeder)
    {
        if ($seeder instanceof Closure) {
            return $this->container->call($seeder);
        }

        $this->container->call($seeder, [], 'seed');
    }
}
