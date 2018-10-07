<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Database\HasRolesAndAbilities;

class CustomAuthorityTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_give_and_remove_abilities($provider)
    {
        list($bouncer, $account) = $provider(1, Account::class);

        $bouncer->allow($account)->to('edit-site');

        $this->assertTrue($bouncer->can('edit-site'));

        $bouncer->disallow($account)->to('edit-site');

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_give_and_remove_roles($provider)
    {
        list($bouncer, $account) = $provider(1, Account::class);

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($account);

        $editor = $bouncer->role()->create(['name' => 'editor']);
        $bouncer->allow($editor)->to('edit-site');
        $bouncer->assign($editor)->to($account);

        $this->assertTrue($bouncer->can('edit-site'));

        $bouncer->retract('admin')->from($account);
        $bouncer->retract($editor)->from($account);

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_disallow_abilities_on_roles($provider)
    {
        list($bouncer, $account) = $provider(1, Account::class);

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->disallow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($account);

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_check_roles($provider)
    {
        list($bouncer, $account) = $provider(1, Account::class);

        $this->assertTrue($bouncer->is($account)->notA('moderator'));
        $this->assertTrue($bouncer->is($account)->notAn('editor'));
        $this->assertFalse($bouncer->is($account)->an('admin'));

        $bouncer = $this->bouncer($account = Account::create());

        $bouncer->assign('moderator')->to($account);
        $bouncer->assign('editor')->to($account);

        $this->assertTrue($bouncer->is($account)->a('moderator'));
        $this->assertTrue($bouncer->is($account)->an('editor'));
        $this->assertFalse($bouncer->is($account)->notAn('editor'));
        $this->assertFalse($bouncer->is($account)->an('admin'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_check_multiple_roles($provider)
    {
        list($bouncer, $account) = $provider(1, Account::class);

        $this->assertTrue($bouncer->is($account)->notAn('editor', 'moderator'));
        $this->assertTrue($bouncer->is($account)->notAn('admin', 'moderator'));

        $bouncer = $this->bouncer($account = Account::create());
        $bouncer->assign('moderator')->to($account);
        $bouncer->assign('editor')->to($account);

        $this->assertTrue($bouncer->is($account)->a('subscriber', 'moderator'));
        $this->assertTrue($bouncer->is($account)->an('admin', 'editor'));
        $this->assertTrue($bouncer->is($account)->all('editor', 'moderator'));
        $this->assertFalse($bouncer->is($account)->notAn('editor', 'moderator'));
        $this->assertFalse($bouncer->is($account)->all('admin', 'moderator'));
    }
}
