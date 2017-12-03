<?php

use Illuminate\Database\Eloquent\Model;

class AbilitiesForModelsTest extends BaseTestCase
{
    public function test_model_blanket_ability()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1)->dontCache();

        $bouncer->allow($user1)->to('edit', User::class);

        $this->assertTrue($bouncer->cannot('edit'));
        $this->assertTrue($bouncer->can('edit', User::class));
        $this->assertTrue($bouncer->can('edit', $user2));

        $bouncer->disallow($user1)->to('edit', User::class);

        $this->assertTrue($bouncer->cannot('edit'));
        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->cannot('edit', $user2));

        $bouncer->disallow($user1)->to('edit');

        $this->assertTrue($bouncer->cannot('edit'));
        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->cannot('edit', $user2));
    }

    public function test_individual_model_ability()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1)->dontCache();

        $bouncer->allow($user1)->to('edit', $user2);

        $this->assertTrue($bouncer->cannot('edit'));
        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->cannot('edit', $user1));
        $this->assertTrue($bouncer->can('edit', $user2));

        $bouncer->disallow($user1)->to('edit', User::class);

        $this->assertTrue($bouncer->can('edit', $user2));

        $bouncer->disallow($user1)->to('edit', $user1);

        $this->assertTrue($bouncer->can('edit', $user2));

        $bouncer->disallow($user1)->to('edit', $user2);

        $this->assertTrue($bouncer->cannot('edit'));
        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->cannot('edit', $user1));
        $this->assertTrue($bouncer->cannot('edit', $user2));
    }

    public function test_blanket_ability_and_individual_model_ability_are_kept_separate()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1)->dontCache();

        $bouncer->allow($user1)->to('edit', User::class);
        $bouncer->allow($user1)->to('edit', $user2);

        $this->assertTrue($bouncer->can('edit', User::class));
        $this->assertTrue($bouncer->can('edit', $user2));

        $bouncer->disallow($user1)->to('edit', User::class);

        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->can('edit', $user2));
    }

    public function test_allowing_on_non_existent_model_throws()
    {
        $this->setExpectedException('InvalidArgumentException');

        $user1 = User::create();
        $user2 = new User;

        $bouncer = $this->bouncer($user1);

        $bouncer->allow($user1)->to('delete', $user2);
    }

    public function test_can_create_an_ability_for_a_model()
    {
        $ability = $this->bouncer()->ability()->createForModel(Account::class, 'delete');

        $this->assertEquals('Account', $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
        $this->assertNull($ability->entity_id);
    }

    public function test_can_create_an_ability_for_a_model_plus_extra_attributes()
    {
        $ability = $this->bouncer()->ability()->createForModel(Account::class, [
            'name' => 'delete',
            'title' => 'Delete Accounts',
        ]);

        $this->assertEquals('Delete Accounts', $ability->title);
        $this->assertEquals('Account', $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
        $this->assertNull($ability->entity_id);
    }

    public function test_can_create_an_ability_for_a_model_instance()
    {
        $user = User::create();

        $ability = $this->bouncer()->ability()->createForModel($user, 'delete');

        $this->assertEquals($user->id, $ability->entity_id);
        $this->assertEquals('User', $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
    }

    public function test_can_create_an_ability_for_a_model_instance_plus_extra_attributes()
    {
        $user = User::create();

        $ability = $this->bouncer()->ability()->createForModel($user, [
            'name' => 'delete',
            'title' => 'Delete this user',
        ]);

        $this->assertEquals('Delete this user', $ability->title);
        $this->assertEquals($user->id, $ability->entity_id);
        $this->assertEquals('User', $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
    }

    public function test_can_create_an_ability_for_all_models()
    {
        $ability = $this->bouncer()->ability()->createForModel('*', 'delete');

        $this->assertEquals('*', $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
        $this->assertNull($ability->entity_id);
    }

    public function test_can_create_an_ability_for_all_models_plus_extra_attributes()
    {
        $ability = $this->bouncer()->ability()->createForModel('*', [
            'name' => 'delete',
            'title' => 'Delete everything',
        ]);

        $this->assertEquals('Delete everything', $ability->title);
        $this->assertEquals('*', $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
        $this->assertNull($ability->entity_id);
    }
}
