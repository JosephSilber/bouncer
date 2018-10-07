<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Database\Ability;

class TitledAbilitiesTest extends BaseTestCase
{
    /**
     * @test
     */
    function allowing_simple_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('access-dashboard', null, [
            'title' => 'Dashboard administration',
        ]);

        $this->seeTitledAbility('Dashboard administration');
    }

    /**
     * @test
     */
    function allowing_model_class_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('create', User::class, [
            'title' => 'Create users',
        ]);

        $this->seeTitledAbility('Create users');
    }

    /**
     * @test
     */
    function allowing_model_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('delete', $user, [
            'title' => 'Delete user #1',
        ]);

        $this->seeTitledAbility('Delete user #1');
    }

    /**
     * @test
     */
    function allowing_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->everything([
            'title' => 'Omnipotent',
        ]);

        $this->seeTitledAbility('Omnipotent');
    }

    /**
     * @test
     */
    function allowing_to_manage_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toManage(User::class, [
            'title' => 'Manage users',
        ]);

        $this->seeTitledAbility('Manage users');
    }

    /**
     * @test
     */
    function allowing_to_manage_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toManage($user, [
            'title' => 'Manage user #1',
        ]);

        $this->seeTitledAbility('Manage user #1');
    }

    /**
     * @test
     */
    function allowing_an_ability_on_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('create')->everything([
            'title' => 'Create anything',
        ]);

        $this->seeTitledAbility('Create anything');
    }

    /**
     * @test
     */
    function allowing_to_own_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toOwn(Account::class, [
            'title' => 'Manage onwed account',
        ]);

        $this->seeTitledAbility('Manage onwed account');
    }

    /**
     * @test
     */
    function allowing_to_own_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toOwn($user, [
            'title' => 'Manage user #1 when owned',
        ]);

        $this->seeTitledAbility('Manage user #1 when owned');
    }

    /**
     * @test
     */
    function allowing_to_own_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toOwnEverything([
            'title' => 'Manage anything onwed',
        ]);

        $this->seeTitledAbility('Manage anything onwed');
    }

    /**
     * @test
     */
    function forbidding_simple_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->to('access-dashboard', null, [
            'title' => 'Dashboard administration',
        ]);

        $this->seeTitledAbility('Dashboard administration');
    }

    /**
     * @test
     */
    function forbidding_model_class_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->to('create', User::class, [
            'title' => 'Create users',
        ]);

        $this->seeTitledAbility('Create users');
    }

    /**
     * @test
     */
    function forbidding_model_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->to('delete', $user, [
            'title' => 'Delete user #1',
        ]);

        $this->seeTitledAbility('Delete user #1');
    }

    /**
     * @test
     */
    function forbidding_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->everything([
            'title' => 'Omnipotent',
        ]);

        $this->seeTitledAbility('Omnipotent');
    }

    /**
     * @test
     */
    function forbidding_to_manage_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toManage(User::class, [
            'title' => 'Manage users',
        ]);

        $this->seeTitledAbility('Manage users');
    }

    /**
     * @test
     */
    function forbidding_to_manage_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toManage($user, [
            'title' => 'Manage user #1',
        ]);

        $this->seeTitledAbility('Manage user #1');
    }

    /**
     * @test
     */
    function forbidding_an_ability_on_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->to('create')->everything([
            'title' => 'Create anything',
        ]);

        $this->seeTitledAbility('Create anything');
    }

    /**
     * @test
     */
    function forbidding_to_own_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toOwn(Account::class, [
            'title' => 'Manage onwed account',
        ]);

        $this->seeTitledAbility('Manage onwed account');
    }

    /**
     * @test
     */
    function forbidding_to_own_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toOwn($user, [
            'title' => 'Manage user #1 when owned',
        ]);

        $this->seeTitledAbility('Manage user #1 when owned');
    }

    /**
     * @test
     */
    function forbidding_to_own_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toOwnEverything([
            'title' => 'Manage anything onwed',
        ]);

        $this->seeTitledAbility('Manage anything onwed');
    }

    /**
     * Assert that there's an ability with the given title in the DB.
     *
     * @param  string  $title
     * @return void
     */
    protected function seeTitledAbility($title)
    {
        $this->assertTrue(Ability::where(compact('title'))->exists());
    }
}
