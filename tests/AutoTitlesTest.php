<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class AutoTitlesTest extends BaseTestCase
{
    /**
     * @test
     */
    function role_title_is_never_overwritten()
    {
        $role = Role::create(['name' => 'admin', 'title' => 'Something Else']);

        $this->assertEquals('Something Else', $role->title);
    }

    /**
     * @test
     */
    function role_title_is_capitalized()
    {
        $role = Role::create(['name' => 'admin']);

        $this->assertEquals('Admin', $role->title);
    }

    /**
     * @test
     */
    function role_title_with_spaces()
    {
        $role = Role::create(['name' => 'site admin']);

        $this->assertEquals('Site admin', $role->title);
    }

    /**
     * @test
     */
    function role_title_with_dashes()
    {
        $role = Role::create(['name' => 'site-admin']);

        $this->assertEquals('Site admin', $role->title);
    }

    /**
     * @test
     */
    function role_title_with_underscores()
    {
        $role = Role::create(['name' => 'site_admin']);

        $this->assertEquals('Site admin', $role->title);
    }

    /**
     * @test
     */
    function role_title_with_camel_casing()
    {
        $role = Role::create(['name' => 'siteAdmin']);

        $this->assertEquals('Site admin', $role->title);
    }

    /**
     * @test
     */
    function role_title_with_studly_casing()
    {
        $role = Role::create(['name' => 'SiteAdmin']);

        $this->assertEquals('Site admin', $role->title);
    }

    /**
     * @test
     */
    function ability_title_is_never_overwritten()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('ban-users', null, [
            'title' => 'Something Else',
        ]);

        $this->assertEquals('Something Else', $bouncer->ability()->first()->title);
    }

    /**
     * @test
     */
    function ability_title_is_set_for_wildcards()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->everything();

        $this->assertEquals('All abilities', $bouncer->ability()->first()->title);
    }

    /**
     * @test
     */
    function ability_title_is_set_for_restricted_wildcards()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('*');

        $this->assertEquals('All simple abilities', $bouncer->ability()->first()->title);
    }

    /**
     * @test
     */
    function ability_title_is_set_for_simple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('ban-users');

        $this->assertEquals('Ban users', $bouncer->ability()->first()->title);
    }

    /**
     * @test
     */
    function ability_title_is_set_for_blanket_ownership_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toOwnEverything();

        $this->assertEquals('Manage everything owned', $bouncer->ability()->first()->title);
    }

    /**
     * @test
     */
    function ability_title_is_set_for_restricted_ownership_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toOwnEverything()->to('edit');

        $this->assertEquals('Edit everything owned', $bouncer->ability()->first()->title);
    }

    /**
     * @test
     */
    function ability_title_is_set_for_management_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toManage(User::class);

        $this->assertEquals('Manage users', $bouncer->ability()->first()->title);
    }

    /**
     * @test
     */
    function ability_title_is_set_for_blanket_model_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('create', User::class);

        $this->assertEquals('Create users', $bouncer->ability()->first()->title);
    }

    /**
     * @test
     */
    function ability_title_is_set_for_regular_model_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('delete', User::create());

        $this->assertEquals('Delete user #2', $bouncer->ability()->first()->title);
    }

    /**
     * @test
     */
    function ability_title_is_set_for_a_global_action_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('delete')->everything();

        $this->assertEquals('Delete everything', $bouncer->ability()->first()->title);
    }
}
