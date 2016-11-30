<?php

use Silber\Bouncer\Database\Ability;

class TitledAbilitiesTest extends BaseTestCase
{
    public function test_allowing_simple_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('access-dashboard', null, [
            'title' => 'Dashboard administration',
        ]);

        $this->seeTitledAbility('Dashboard administration');
    }

    public function test_allowing_model_class_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('create', User::class, [
            'title' => 'Create users',
        ]);

        $this->seeTitledAbility('Create users');
    }

    public function test_allowing_model_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('delete', $user, [
            'title' => 'Delete user #1',
        ]);

        $this->seeTitledAbility('Delete user #1');
    }

    public function test_allowing_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->everything([
            'title' => 'Omnipotent',
        ]);

        $this->seeTitledAbility('Omnipotent');
    }

    public function test_allowing_to_manage_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toManage(User::class, [
            'title' => 'Manage users',
        ]);

        $this->seeTitledAbility('Manage users');
    }

    public function test_allowing_to_manage_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toManage($user, [
            'title' => 'Manage user #1',
        ]);

        $this->seeTitledAbility('Manage user #1');
    }

    public function test_allowing_an_ability_on_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toAlways('create', [
            'title' => 'Create anything',
        ]);

        $this->seeTitledAbility('Create anything');
    }

    public function test_allowing_to_own_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toOwn(Account::class, [
            'title' => 'Manage onwed account',
        ]);

        $this->seeTitledAbility('Manage onwed account');
    }

    public function test_allowing_to_own_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toOwn($user, [
            'title' => 'Manage user #1 when owned',
        ]);

        $this->seeTitledAbility('Manage user #1 when owned');
    }

    public function test_allowing_to_own_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toOwnEverything([
            'title' => 'Manage anything onwed',
        ]);

        $this->seeTitledAbility('Manage anything onwed');
    }

    public function test_forbidding_simple_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->to('access-dashboard', null, [
            'title' => 'Dashboard administration',
        ]);

        $this->seeTitledAbility('Dashboard administration');
    }

    public function test_forbidding_model_class_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->to('create', User::class, [
            'title' => 'Create users',
        ]);

        $this->seeTitledAbility('Create users');
    }

    public function test_forbidding_model_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->to('delete', $user, [
            'title' => 'Delete user #1',
        ]);

        $this->seeTitledAbility('Delete user #1');
    }

    public function test_forbidding_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->everything([
            'title' => 'Omnipotent',
        ]);

        $this->seeTitledAbility('Omnipotent');
    }

    public function test_forbidding_to_manage_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toManage(User::class, [
            'title' => 'Manage users',
        ]);

        $this->seeTitledAbility('Manage users');
    }

    public function test_forbidding_to_manage_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toManage($user, [
            'title' => 'Manage user #1',
        ]);

        $this->seeTitledAbility('Manage user #1');
    }

    public function test_forbidding_an_ability_on_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toAlways('create', [
            'title' => 'Create anything',
        ]);

        $this->seeTitledAbility('Create anything');
    }

    public function test_forbidding_to_own_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toOwn(Account::class, [
            'title' => 'Manage onwed account',
        ]);

        $this->seeTitledAbility('Manage onwed account');
    }

    public function test_forbidding_to_own_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toOwn($user, [
            'title' => 'Manage user #1 when owned',
        ]);

        $this->seeTitledAbility('Manage user #1 when owned');
    }

    public function test_forbidding_to_own_everything()
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
