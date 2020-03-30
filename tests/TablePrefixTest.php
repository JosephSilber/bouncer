<?php

namespace Silber\Bouncer\Tests;

class TablePrefixTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    protected function registerDatabaseContainerBindings()
    {
        parent::registerDatabaseContainerBindings();

        $this->db()->connection()->setTablePrefix('bouncer_');
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function test_ability_queries_work_with_prefix($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->everything();

        $this->assertTrue($bouncer->can('do-something'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function test_role_queries_work_with_prefix($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->assign('artisan')->to($user);

        $this->assertTrue($bouncer->is($user)->an('artisan'));
    }
}
