<?php

namespace Silber\Bouncer\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

use Workbench\App\Models\User;
use Workbench\App\Models\Account;

class SyncTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function syncing_roles($provider)
    {
        [$bouncer, $user] = $provider();

        $admin      = $this->role('admin');
        $editor     = $this->role('editor');
        $reviewer   = $this->role('reviewer');
        $subscriber = $this->role('subscriber');

        $user->assign([$admin, $editor]);

        $this->assertTrue($bouncer->is($user)->all($admin, $editor));

        $bouncer->sync($user)->roles([$editor->id, $reviewer->name, $subscriber]);

        $this->assertTrue($bouncer->is($user)->all($editor, $reviewer, $subscriber));
        $this->assertTrue($bouncer->is($user)->notAn($admin));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function syncing_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $editSite = Ability::create(['name' => 'edit-site']);
        $banUsers = Ability::create(['name' => 'ban-users']);
        $accessDashboard = Ability::create(['name' => 'access-dashboard']);

        $bouncer->allow($user)->to([$editSite, $banUsers]);

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->cannot('access-dashboard'));

        $bouncer->sync($user)->abilities([$banUsers->id, 'access-dashboard']);

        $this->assertTrue($bouncer->cannot('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->can('access-dashboard'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function syncing_abilities_with_a_map($provider)
    {
        [$bouncer, $user] = $provider();

        $deleteUser = Ability::createForModel($user, 'delete');
        $createAccounts = Ability::createForModel(Account::class, 'create');

        $bouncer->allow($user)->to([$deleteUser, $createAccounts]);

        $this->assertTrue($bouncer->can('delete', $user));
        $this->assertTrue($bouncer->can('create', Account::class));

        $bouncer->sync($user)->abilities([
            'access-dashboard',
            'create' => Account::class,
            'view' => $user,
        ]);

        $this->assertTrue($bouncer->cannot('delete', $user));
        $this->assertTrue($bouncer->cannot('view', User::class));
        $this->assertTrue($bouncer->can('create', Account::class));
        $this->assertTrue($bouncer->can('view', $user));
        $this->assertTrue($bouncer->can('access-dashboard'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function syncing_forbidden_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $editSite = Ability::create(['name' => 'edit-site']);
        $banUsers = Ability::create(['name' => 'ban-users']);
        $accessDashboard = Ability::create(['name' => 'access-dashboard']);

        $bouncer->allow($user)->everything();
        $bouncer->forbid($user)->to([$editSite, $banUsers->id]);

        $this->assertTrue($bouncer->cannot('edit-site'));
        $this->assertTrue($bouncer->cannot('ban-users'));
        $this->assertTrue($bouncer->can('access-dashboard'));

        $bouncer->sync($user)->forbiddenAbilities([$banUsers->id, 'access-dashboard']);

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->cannot('ban-users'));
        $this->assertTrue($bouncer->cannot('access-dashboard'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function syncing_a_roles_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $editSite = Ability::create(['name' => 'edit-site']);
        $banUsers = Ability::create(['name' => 'ban-users']);
        $accessDashboard = Ability::create(['name' => 'access-dashboard']);

        $bouncer->assign('admin')->to($user);
        $bouncer->allow('admin')->to([$editSite, $banUsers]);

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->cannot('access-dashboard'));

        $bouncer->sync('admin')->abilities([$banUsers->id, 'access-dashboard']);

        $this->assertTrue($bouncer->cannot('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->can('access-dashboard'));
    }

    #[Test]
    public function syncing_user_abilities_does_not_alter_role_abilities_with_same_id()
    {
        $user = User::create(['id' => 1]);
        $bouncer = $this->bouncer($user);
        $role = $bouncer->role()->create(['id' => 1, 'name' => 'alcoholic']);

        $bouncer->allow($user)->to(['eat', 'drink']);
        $bouncer->allow($role)->to('drink');

        $bouncer->sync($user)->abilities(['eat']);

        $this->assertTrue($user->can('eat'));
        $this->assertTrue($user->cannot('drink'));
        $this->assertTrue($role->can('drink'));
    }

    #[Test]
    public function syncing_abilities_does_not_affect_another_entity_type_with_same_id()
    {
        $user = User::create(['id' => 1]);
        $account = Account::create(['id' => 1]);

        $bouncer = $this->bouncer();

        $bouncer->allow($user)->to('relax');
        $bouncer->allow($account)->to('relax');

        $this->assertTrue($user->can('relax'));
        $this->assertTrue($account->can('relax'));

        $bouncer->sync($user)->abilities([]);

        $this->assertTrue($user->cannot('relax'));
        $this->assertTrue($account->can('relax'));
    }

    #[Test]
    public function syncing_roles_does_not_affect_another_entity_type_with_same_id()
    {
        $user = User::create(['id' => 1]);
        $account = Account::create(['id' => 1]);

        $bouncer = $this->bouncer();

        $bouncer->assign('admin')->to($user);
        $bouncer->assign('admin')->to($account);

        $this->assertTrue($user->isAn('admin'));
        $this->assertTrue($account->isAn('admin'));

        $bouncer->sync($user)->roles([]);

        $this->assertTrue($user->isNotAn('admin'));
        $this->assertTrue($account->isAn('admin'));
    }

    /**
     * Create a new role with the given name.
     *
     * @param  string  $name
     * @return \Silber\Bouncer\Database\Role
     */
    protected function role($name)
    {
        return Role::create(compact('name'));
    }
}
