<?php

namespace Silber\Bouncer\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

use Silber\Bouncer\Database\Models;
use Workbench\App\Models\User;
use Workbench\App\Models\UserWithSoftDeletes;
use Workbench\App\Models\Account;

class HasRolesAndAbilitiesTraitTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function get_abilities_gets_all_allowed_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->allow($user)->to('create-posts');
        $bouncer->assign('admin')->to($user);

        $bouncer->forbid($user)->to('create-sites');
        $bouncer->allow('editor')->to('edit-posts');

        $this->assertEquals(
            ['create-posts', 'edit-site'],
            $user->getAbilities()->pluck('name')->sort()->values()->all()
        );
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function get_forbidden_abilities_gets_all_forbidden_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->forbid('admin')->to('edit-site');
        $bouncer->forbid($user)->to('create-posts');
        $bouncer->assign('admin')->to($user);

        $bouncer->allow($user)->to('create-sites');
        $bouncer->forbid('editor')->to('edit-posts');

        $this->assertEquals(
            ['create-posts', 'edit-site'],
            $user->getForbiddenAbilities()->pluck('name')->sort()->values()->all()
        );
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_give_and_remove_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow('edit-site');

        $this->assertTrue($bouncer->can('edit-site'));

        $user->disallow('edit-site');

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_give_and_remove_model_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow('delete', $user);

        $this->assertTrue($bouncer->cannot('delete'));
        $this->assertTrue($bouncer->cannot('delete', User::class));
        $this->assertTrue($bouncer->can('delete', $user));

        $user->disallow('delete', $user);

        $this->assertTrue($bouncer->cannot('delete', $user));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_give_and_remove_ability_for_everything($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow()->everything();

        $this->assertTrue($bouncer->can('delete'));
        $this->assertTrue($bouncer->can('delete', '*'));
        $this->assertTrue($bouncer->can('*', '*'));

        $user->disallow()->everything();

        $this->assertTrue($bouncer->cannot('delete'));
        $this->assertTrue($bouncer->cannot('delete', '*'));
        $this->assertTrue($bouncer->cannot('*', '*'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_forbid_and_unforbid_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow('edit-site');
        $user->forbid('edit-site');

        $this->assertTrue($bouncer->cannot('edit-site'));

        $user->unforbid('edit-site');

        $this->assertTrue($bouncer->can('edit-site'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_forbid_and_unforbid_model_abilities($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow('delete', $user);
        $user->forbid('delete', $user);

        $this->assertTrue($bouncer->cannot('delete', $user));

        $user->unforbid('delete', $user);

        $this->assertTrue($bouncer->can('delete', $user));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_forbid_and_unforbid_everything($provider)
    {
        [$bouncer, $user] = $provider();

        $user->allow('delete', $user);
        $user->forbid()->everything();

        $this->assertTrue($bouncer->cannot('delete', $user));

        $user->unforbid()->everything();

        $this->assertTrue($bouncer->can('delete', $user));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_assign_and_retract_roles($provider)
    {
        [$bouncer, $user] = $provider();

        $bouncer->allow('admin')->to('edit-site');
        $user->assign('admin');

        $this->assertEquals(['admin'], $user->getRoles()->all());
        $this->assertTrue($bouncer->can('edit-site'));

        $user->retract('admin');

        $this->assertEquals([], $user->getRoles()->all());
        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_check_roles($provider)
    {
        [$bouncer, $user] = $provider();

        $this->assertTrue($user->isNotAn('admin'));
        $this->assertFalse($user->isAn('admin'));

        $this->assertTrue($user->isNotA('admin'));
        $this->assertFalse($user->isA('admin'));

        $user->assign('admin');

        $this->assertTrue($user->isAn('admin'));
        $this->assertFalse($user->isAn('editor'));
        $this->assertFalse($user->isNotAn('admin'));
        $this->assertTrue($user->isNotAn('editor'));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function can_check_multiple_roles($provider)
    {
        [$bouncer, $user] = $provider();

        $this->assertFalse($user->isAn('admin', 'editor'));

        $user->assign('moderator');
        $user->assign('editor');

        $this->assertTrue($user->isAn('admin', 'moderator'));
        $this->assertTrue($user->isAll('editor', 'moderator'));
        $this->assertFalse($user->isAll('moderator', 'admin'));
    }

    #[Test]
    public function deleting_a_model_deletes_the_permissions_pivot_table_records()
    {
        $bouncer = $this->bouncer();

        $user1 = User::create();
        $user2 = User::create();

        $bouncer->allow($user1)->everything();
        $bouncer->allow($user2)->everything();

        $this->assertEquals(2, $this->db()->table('permissions')->count());

        $user1->delete();

        $this->assertEquals(1, $this->db()->table('permissions')->count());
    }

    #[Test]
    public function soft_deleting_a_model_persists_the_permissions_pivot_table_records()
    {
        Models::setUsersModel(UserWithSoftDeletes::class);

        $bouncer = $this->bouncer();

        $user1 = UserWithSoftDeletes::create();
        $user2 = UserWithSoftDeletes::create();

        $bouncer->allow($user1)->everything();
        $bouncer->allow($user2)->everything();

        $this->assertEquals(2, $this->db()->table('permissions')->count());

        $user1->delete();

        $this->assertEquals(2, $this->db()->table('permissions')->count());
    }

    #[Test]
    public function deleting_a_model_deletes_the_assigned_roles_pivot_table_records()
    {
        $bouncer = $this->bouncer();

        $user1 = User::create();
        $user2 = User::create();

        $bouncer->assign('admin')->to($user1);
        $bouncer->assign('admin')->to($user2);

        $this->assertEquals(2, $this->db()->table('assigned_roles')->count());

        $user1->delete();

        $this->assertEquals(1, $this->db()->table('assigned_roles')->count());
    }

    #[Test]
    public function soft_deleting_a_model_persists_the_assigned_roles_pivot_table_records()
    {
        Models::setUsersModel(UserWithSoftDeletes::class);

        $bouncer = $this->bouncer();

        $user1 = UserWithSoftDeletes::create();
        $user2 = UserWithSoftDeletes::create();

        $bouncer->assign('admin')->to($user1);
        $bouncer->assign('admin')->to($user2);

        $this->assertEquals(2, $this->db()->table('assigned_roles')->count());

        $user1->delete();

        $this->assertEquals(2, $this->db()->table('assigned_roles')->count());
    }
}
