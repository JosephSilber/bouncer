<?php

namespace Silber\Bouncer\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

use Workbench\App\Models\User;
use Workbench\App\Models\Account;

class TablePrefixTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    protected function registerDatabaseContainerBindings()
    {
        parent::registerDatabaseContainerBindings();

        $this->db()->connection()->setTablePrefix('bouncer_');
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function test_ability_queries_work_with_prefix($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow($user)->everything();

        $this->assertTrue($bouncer->can('do-something'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function test_role_queries_work_with_prefix($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->assign('artisan')->to($user);

        $this->assertTrue($bouncer->is($user)->an('artisan'));
    }
}
