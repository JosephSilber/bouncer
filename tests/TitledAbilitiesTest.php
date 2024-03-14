<?php

namespace Silber\Bouncer\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

use Silber\Bouncer\Database\Ability;

use Workbench\App\Models\User;
use Workbench\App\Models\Account;

class TitledAbilitiesTest extends BaseTestCase
{
    #[Test]
    public function allowing_simple_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('access-dashboard', null, [
            'title' => 'Dashboard administration',
        ]);

        $this->seeTitledAbility('Dashboard administration');
    }

    #[Test]
    public function allowing_model_class_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('create', User::class, [
            'title' => 'Create users',
        ]);

        $this->seeTitledAbility('Create users');
    }

    #[Test]
    public function allowing_model_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('delete', $user, [
            'title' => 'Delete user #1',
        ]);

        $this->seeTitledAbility('Delete user #1');
    }

    #[Test]
    public function allowing_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->everything([
            'title' => 'Omnipotent',
        ]);

        $this->seeTitledAbility('Omnipotent');
    }

    #[Test]
    public function allowing_to_manage_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toManage(User::class, [
            'title' => 'Manage users',
        ]);

        $this->seeTitledAbility('Manage users');
    }

    #[Test]
    public function allowing_to_manage_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toManage($user, [
            'title' => 'Manage user #1',
        ]);

        $this->seeTitledAbility('Manage user #1');
    }

    #[Test]
    public function allowing_an_ability_on_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('create')->everything([
            'title' => 'Create anything',
        ]);

        $this->seeTitledAbility('Create anything');
    }

    #[Test]
    public function allowing_to_own_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toOwn(Account::class, [
            'title' => 'Manage onwed account',
        ]);

        $this->seeTitledAbility('Manage onwed account');
    }

    #[Test]
    public function allowing_to_own_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toOwn($user, [
            'title' => 'Manage user #1 when owned',
        ]);

        $this->seeTitledAbility('Manage user #1 when owned');
    }

    #[Test]
    public function allowing_to_own_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->toOwnEverything([
            'title' => 'Manage anything onwed',
        ]);

        $this->seeTitledAbility('Manage anything onwed');
    }

    #[Test]
    public function forbidding_simple_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->to('access-dashboard', null, [
            'title' => 'Dashboard administration',
        ]);

        $this->seeTitledAbility('Dashboard administration');
    }

    #[Test]
    public function forbidding_model_class_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->to('create', User::class, [
            'title' => 'Create users',
        ]);

        $this->seeTitledAbility('Create users');
    }

    #[Test]
    public function forbidding_model_ability()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->to('delete', $user, [
            'title' => 'Delete user #1',
        ]);

        $this->seeTitledAbility('Delete user #1');
    }

    #[Test]
    public function forbidding_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->everything([
            'title' => 'Omnipotent',
        ]);

        $this->seeTitledAbility('Omnipotent');
    }

    #[Test]
    public function forbidding_to_manage_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toManage(User::class, [
            'title' => 'Manage users',
        ]);

        $this->seeTitledAbility('Manage users');
    }

    #[Test]
    public function forbidding_to_manage_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toManage($user, [
            'title' => 'Manage user #1',
        ]);

        $this->seeTitledAbility('Manage user #1');
    }

    #[Test]
    public function forbidding_an_ability_on_everything()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->to('create')->everything([
            'title' => 'Create anything',
        ]);

        $this->seeTitledAbility('Create anything');
    }

    #[Test]
    public function forbidding_to_own_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toOwn(Account::class, [
            'title' => 'Manage onwed account',
        ]);

        $this->seeTitledAbility('Manage onwed account');
    }

    #[Test]
    public function forbidding_to_own_a_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->forbid($user)->toOwn($user, [
            'title' => 'Manage user #1 when owned',
        ]);

        $this->seeTitledAbility('Manage user #1 when owned');
    }

    #[Test]
    public function forbidding_to_own_everything()
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
