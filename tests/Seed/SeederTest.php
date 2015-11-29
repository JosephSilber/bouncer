<?php

use Silber\Bouncer\Seed\Seeder;
use Illuminate\Container\Container;

class SeederTest extends PHPUnit_Framework_TestCase
{
    public function test_seeder_runs_registered_closure()
    {
        $this->seeder()->register($this->seederCallback())->run();

        $this->assertEquals(['call'], BouncerSeederCallback::$calls);

        BouncerSeederCallback::reset();
    }

    public function test_seeder_runs_registered_class_callback()
    {
        $this->seeder()->register('BouncerSeederCallback@run')->run();

        $this->assertEquals(['run'], BouncerSeederCallback::$calls);

        BouncerSeederCallback::reset();
    }

    public function test_seeder_runs_registered_class_callback_with_default_seed_method()
    {
         $this->seeder()->register('BouncerSeederCallback')->run();

        $this->assertEquals(['seed'], BouncerSeederCallback::$calls);

        BouncerSeederCallback::reset();
    }

    public function test_seeder_runs_all_registered_seeders()
    {
        $this->seeder()
             ->register('BouncerSeederCallback@run')
             ->register('BouncerSeederCallback')
             ->register($this->seederCallback())
             ->run();

        $this->assertEquals(['run', 'seed', 'call'], BouncerSeederCallback::$calls);

        BouncerSeederCallback::reset();
    }

    /**
     * Get an instance of the seeder.
     *
     * @return \Silber\Bouncer\Seed\Seeder
     */
    protected function seeder()
    {
        return new Seeder(new Container);
    }

    /**
     * Get a callback to be regstered with the seeder class.
     *
     * @return \Closure
     */
    protected function seederCallback()
    {
        return function () {
            BouncerSeederCallback::call();
        };
    }
}

class BouncerSeederCallback
{
    public static $calls = [];

    public function seed()
    {
        static::$calls[] = 'seed';
    }

    public function run()
    {
        static::$calls[] = 'run';
    }

    public static function call()
    {
        static::$calls[] = 'call';
    }

    public static function reset()
    {
        static::$calls = [];
    }
}
