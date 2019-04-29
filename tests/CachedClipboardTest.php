<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\CachedClipboard;

use Illuminate\Cache\ArrayStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class CachedClipboardTest extends BaseTestCase
{
    function setUp()
    {
        parent::setUp();

        $this->clipboard = new CachedClipboard(new ArrayStore);
    }

    /**
     * @test
     */
    function it_caches_abilities()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('ban-users');

        $this->assertEquals(['ban-users'], $this->getAbilities($user));

        $bouncer->allow($user)->to('create-users');

        $this->assertEquals(['ban-users'], $this->getAbilities($user));
    }

    /**
     * @test
     */
    function it_caches_empty_abilities()
    {
        $user = User::create();

        $this->assertInstanceOf(Collection::class, $this->clipboard->getAbilities($user));
        $this->assertInstanceOf(Collection::class, $this->clipboard->getAbilities($user));
    }

    /**
     * @test
     */
    function it_caches_roles()
    {
        $bouncer = $this->bouncer($user = User::create());

        $bouncer->assign('editor')->to($user);

        $this->assertTrue($bouncer->is($user)->an('editor'));

        $bouncer->assign('moderator')->to($user);

        $this->assertFalse($bouncer->is($user)->a('moderator'));
    }

    /**
     * @test
     */
    function it_always_checks_roles_in_the_cache()
    {
        $bouncer = $this->bouncer($user = User::create());
        $admin = $bouncer->role()->create(['name' => 'admin']);

        $bouncer->assign($admin)->to($user);

        $this->assertTrue($bouncer->is($user)->an('admin'));

        $this->db()->connection()->enableQueryLog();

        $this->assertTrue($bouncer->is($user)->an($admin));
        $this->assertTrue($bouncer->is($user)->an('admin'));
        $this->assertTrue($bouncer->is($user)->an($admin->id));

        $this->assertEmpty($this->db()->connection()->getQueryLog());

        $this->db()->connection()->disableQueryLog();
    }

    /**
     * @test
     */
    function it_can_refresh_the_cache()
    {
        $cache = new ArrayStore;

        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow($user)->to('create-posts');
        $bouncer->assign('editor')->to($user);
        $bouncer->allow('editor')->to('delete-posts');

        $this->assertEquals(['create-posts', 'delete-posts'], $this->getAbilities($user));

        $bouncer->disallow('editor')->to('delete-posts');
        $bouncer->allow('editor')->to('edit-posts');

        $this->assertEquals(['create-posts', 'delete-posts'], $this->getAbilities($user));

        $bouncer->refresh();

        $this->assertEquals(['create-posts', 'edit-posts'], $this->getAbilities($user));
    }

    /**
     * @test
     */
    function it_can_refresh_the_cache_only_for_one_user()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user = User::create());

        $bouncer->allow('admin')->to('ban-users');
        $bouncer->assign('admin')->to($user1);
        $bouncer->assign('admin')->to($user2);

        $this->assertEquals(['ban-users'], $this->getAbilities($user1));
        $this->assertEquals(['ban-users'], $this->getAbilities($user2));

        $bouncer->disallow('admin')->to('ban-users');
        $bouncer->refreshFor($user1);

        $this->assertEquals([], $this->getAbilities($user1));
        $this->assertEquals(['ban-users'], $this->getAbilities($user2));
    }

    /**
     * Get the name of all of the user's abilities.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @return array
     */
    protected function getAbilities(Model $user)
    {
        return $user->getAbilities($user)->pluck('name')->sort()->values()->all();
    }
}
