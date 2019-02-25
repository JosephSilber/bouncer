<?php

namespace Silber\Bouncer\Tests;

use Exception;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class BouncerSimpleTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_give_and_remove_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $editSite = Ability::create(['name' => 'edit-site']);
        $banUsers = Ability::create(['name' => 'ban-users']);
        $accessDashboard = Ability::create(['name' => 'access-dashboard']);

        $bouncer->allow($user)->to('edit-site');
        $bouncer->allow($user)->to([$banUsers, $accessDashboard->id]);

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->can('access-dashboard'));

        $bouncer->disallow($user)->to($editSite);
        $bouncer->disallow($user)->to('ban-users');
        $bouncer->disallow($user)->to($accessDashboard->id);

        $this->assertTrue($bouncer->cannot('edit-site'));
        $this->assertTrue($bouncer->cannot('ban-users'));
        $this->assertTrue($bouncer->cannot('access-dashboard'));
    }


    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_give_and_remove_abilities_for_everyone($provider)
    {
        list($bouncer, $user) = $provider();

        $editSite = Ability::create(['name' => 'edit-site']);
        $banUsers = Ability::create(['name' => 'ban-users']);
        $accessDashboard = Ability::create(['name' => 'access-dashboard']);

        $bouncer->allowEveryone()->to('edit-site');
        $bouncer->allowEveryone()->to([$banUsers, $accessDashboard->id]);

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->can('access-dashboard'));

        $bouncer->disallowEveryone()->to($editSite);
        $bouncer->disallowEveryone()->to('ban-users');
        $bouncer->disallowEveryone()->to($accessDashboard->id);

        $this->assertTrue($bouncer->cannot('edit-site'));
        $this->assertTrue($bouncer->cannot('ban-users'));
        $this->assertTrue($bouncer->cannot('access-dashboard'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_give_and_remove_wildcard_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to('*');

        $this->assertTrue($bouncer->can('edit-site'));
        $this->assertTrue($bouncer->can('ban-users'));
        $this->assertTrue($bouncer->can('*'));

        $bouncer->disallow($user)->to('*');

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_ignore_duplicate_ability_allowances($provider)
    {
        list($bouncer, $user1, $user2) = $provider(2);

        $bouncer->allow($user1)->to('ban-users');
        $bouncer->allow($user1)->to('ban-users');

        $bouncer->allow($user1)->to('ban', $user2);
        $bouncer->allow($user1)->to('ban', $user2);

        $this->assertCount(2, $user1->abilities);

        $admin = $bouncer->role(['name' => 'admin']);
        $admin->save();

        $bouncer->allow($admin)->to('ban-users');
        $bouncer->allow($admin)->to('ban-users');

        $bouncer->allow($admin)->to('ban', $user1);
        $bouncer->allow($admin)->to('ban', $user1);

        $this->assertCount(2, $admin->abilities);
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_give_and_remove_roles($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow('admin')->to('ban-users');
        $bouncer->assign('admin')->to($user);

        $editor = $bouncer->role()->create(['name' => 'editor']);
        $bouncer->allow($editor)->to('ban-users');
        $bouncer->assign($editor)->to($user);

        $this->assertTrue($bouncer->can('ban-users'));

        $bouncer->retract('admin')->from($user);
        $bouncer->retract($editor)->from($user);

        $this->assertTrue($bouncer->cannot('ban-users'));
    }

    /**
     * @test
     */
    function deleting_a_role_deletes_the_pivot_table_records()
    {
        $bouncer = $this->bouncer();

        $admin = $bouncer->role()->create(['name' => 'admin']);
        $editor = $bouncer->role()->create(['name' => 'editor']);

        $bouncer->allow($admin)->everything();
        $bouncer->allow($editor)->to('edit', User::class);

        $this->assertEquals(2, $this->db()->table('permissions')->count());

        $admin->delete();

        $this->assertEquals(1, $this->db()->table('permissions')->count());
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_give_and_remove_multiple_roles_at_once($provider)
    {
        list($bouncer, $user) = $provider();

        $admin    = $this->role('admin');
        $editor   = $this->role('editor');
        $reviewer = $this->role('reviewer');

        $bouncer->assign(collect([$admin, 'editor', $reviewer->id]))->to($user);

        $this->assertTrue($bouncer->is($user)->all($admin->id, $editor, 'reviewer'));

        $bouncer->retract(['admin', $editor])->from($user);

        $this->assertTrue($bouncer->is($user)->notAn($admin, 'editor'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_give_and_remove_roles_for_multiple_users_at_once($provider)
    {
        list($bouncer, $user1, $user2) = $provider(2);

        $bouncer->assign(['admin', 'editor'])->to([$user1, $user2]);

        $this->assertTrue($bouncer->is($user1)->all('admin', 'editor'));
        $this->assertTrue($bouncer->is($user2)->an('admin', 'editor'));

        $bouncer->retract('admin')->from($user1);
        $bouncer->retract(collect(['admin', 'editor']))->from($user2);

        $this->assertTrue($bouncer->is($user1)->notAn('admin'));
        $this->assertTrue($bouncer->is($user1)->an('editor'));
        $this->assertTrue($bouncer->is($user1)->an('admin', 'editor'));
    }

    /**
     * @test
     */
    function can_ignore_duplicate_role_assignments()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->assign('admin')->to($user);
        $bouncer->assign('admin')->to($user);

        $this->assertCount(1, $user->roles);
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_disallow_abilities_on_roles($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->disallow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($user);

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function disallow_on_roles_does_not_disallow_for_users_with_matching_id($provider)
    {
        list($bouncer, $user) = $provider();

        // Since the user is the first user created, its ID is 1.
        // Creating admin as the first role, it'll have its ID
        // set to 1. Let's test that they're kept separate.
        $bouncer->allow($user)->to('edit-site');
        $bouncer->allow('admin')->to('edit-site');
        $bouncer->disallow('admin')->to('edit-site');

        $this->assertTrue($bouncer->can('edit-site'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_check_user_roles($provider)
    {
        list($bouncer, $user) = $provider();

        $this->assertTrue($bouncer->is($user)->notA('moderator'));
        $this->assertTrue($bouncer->is($user)->notAn('editor'));
        $this->assertFalse($bouncer->is($user)->an('admin'));

        $bouncer = $this->bouncer($user = User::create());

        $bouncer->assign('moderator')->to($user);
        $bouncer->assign('editor')->to($user);

        $this->assertTrue($bouncer->is($user)->a('moderator'));
        $this->assertTrue($bouncer->is($user)->an('editor'));
        $this->assertFalse($bouncer->is($user)->notAn('editor'));
        $this->assertFalse($bouncer->is($user)->an('admin'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_check_multiple_user_roles($provider)
    {
        list($bouncer, $user) = $provider();

        $this->assertTrue($bouncer->is($user)->notAn('editor', 'moderator'));
        $this->assertTrue($bouncer->is($user)->notAn('admin', 'moderator'));

        $bouncer = $this->bouncer($user = User::create());
        $bouncer->assign('moderator')->to($user);
        $bouncer->assign('editor')->to($user);

        $this->assertTrue($bouncer->is($user)->a('subscriber', 'moderator'));
        $this->assertTrue($bouncer->is($user)->an('admin', 'editor'));
        $this->assertTrue($bouncer->is($user)->all('editor', 'moderator'));
        $this->assertFalse($bouncer->is($user)->notAn('editor', 'moderator'));
        $this->assertFalse($bouncer->is($user)->all('admin', 'moderator'));
    }

    /**
     * @test
     */
    function can_get_an_empty_role_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $this->assertInstanceOf(Role::class, $bouncer->role());
    }

    /**
     * @test
     */
    function can_fill_a_role_model()
    {
        $bouncer = $this->bouncer($user = User::create());
        $role = $bouncer->role(['name' => 'test-role']);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('test-role', $role->name);
    }

    /**
     * @test
     */
    function can_get_an_empty_ability_model()
    {
        $bouncer = $this->bouncer($user = User::create());

        $this->assertInstanceOf(Ability::class, $bouncer->ability());
    }

    /**
     * @test
     */
    function can_fill_an_ability_model()
    {
        $bouncer = $this->bouncer($user = User::create());
        $ability = $bouncer->ability(['name' => 'test-ability']);

        $this->assertInstanceOf(Ability::class, $ability);
        $this->assertEquals('test-ability', $ability->name);
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_allow_abilities_from_a_defined_callback($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->define('edit', function ($user, $account) {
            if ( ! $account instanceof Account) {
                return null;
            }

            return $user->id == $account->user_id;
        });

        $this->assertTrue($bouncer->can('edit', new Account(['user_id' => $user->id])));
        $this->assertFalse($bouncer->can('edit', new Account(['user_id' => 99])));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function authorize_method_returns_response_with_correct_message($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to('have-fun');
        $bouncer->allow($user)->to('enjoy-life');

        $this->assertEquals(
            'Bouncer granted permission via ability #2',
            $bouncer->authorize('enjoy-life')->message()
        );

        $this->assertEquals(
            'Bouncer granted permission via ability #1',
            $bouncer->authorize('have-fun')->message()
        );
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function authorize_method_throws_for_unauthorized_abilities($provider)
    {
        list($bouncer) = $provider();

        // The exception class thrown from the "authorize" method
        // has changed between different versions of Laravel,
        // so we cannot check for a specific error class.
        $threw = false;

        try {
            $bouncer->authorize('be-miserable');
        } catch (Exception $e) {
            $threw = true;
        }

        $this->assertTrue($threw);
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
