<?php

namespace Silber\Bouncer\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

use Silber\Bouncer\Database\Ability;
use Illuminate\Database\Eloquent\Model;
use Workbench\App\Models\User;
use Workbench\App\Models\Account;

class AbilitiesForModelsTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function model_blanket_ability($provider)
    {
        [$bouncer, $user1, $user2] = $provider(2);

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

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function individual_model_ability($provider)
    {
        [$bouncer, $user1, $user2] = $provider(2);

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

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function blanket_ability_and_individual_model_ability_are_kept_separate($provider)
    {
        [$bouncer, $user1, $user2] = $provider(2);

        $bouncer->allow($user1)->to('edit', User::class);
        $bouncer->allow($user1)->to('edit', $user2);

        $this->assertTrue($bouncer->can('edit', User::class));
        $this->assertTrue($bouncer->can('edit', $user2));

        $bouncer->disallow($user1)->to('edit', User::class);

        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->can('edit', $user2));
    }

    #[Test]
    #[DataProvider('bouncerProvider')]
    public function allowing_on_non_existent_model_throws($provider)
    {
        $this->expectException('InvalidArgumentException');

        [$bouncer, $user] = $provider();

        $bouncer->allow($user)->to('delete', new User);
    }

    #[Test]
    public function can_create_an_ability_for_a_model()
    {
        $ability = Ability::createForModel(Account::class, 'delete');

        $this->assertEquals(Account::class, $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
        $this->assertNull($ability->entity_id);
    }

    #[Test]
    public function can_create_an_ability_for_a_model_plus_extra_attributes()
    {
        $ability = Ability::createForModel(Account::class, [
            'name' => 'delete',
            'title' => 'Delete Accounts',
        ]);

        $this->assertEquals('Delete Accounts', $ability->title);
        $this->assertEquals(Account::class, $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
        $this->assertNull($ability->entity_id);
    }

    #[Test]
    public function can_create_an_ability_for_a_model_instance()
    {
        $user = User::create();

        $ability = Ability::createForModel($user, 'delete');

        $this->assertEquals($user->id, $ability->entity_id);
        $this->assertEquals(User::class, $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
    }

    #[Test]
    public function can_create_an_ability_for_a_model_instance_plus_extra_attributes()
    {
        $user = User::create();

        $ability = Ability::createForModel($user, [
            'name' => 'delete',
            'title' => 'Delete this user',
        ]);

        $this->assertEquals('Delete this user', $ability->title);
        $this->assertEquals($user->id, $ability->entity_id);
        $this->assertEquals(User::class, $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
    }

    #[Test]
    public function can_create_an_ability_for_all_models()
    {
        $ability = Ability::createForModel('*', 'delete');

        $this->assertEquals('*', $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
        $this->assertNull($ability->entity_id);
    }

    #[Test]
    public function can_create_an_ability_for_all_models_plus_extra_attributes()
    {
        $ability = Ability::createForModel('*', [
            'name' => 'delete',
            'title' => 'Delete everything',
        ]);

        $this->assertEquals('Delete everything', $ability->title);
        $this->assertEquals('*', $ability->entity_type);
        $this->assertEquals('delete', $ability->name);
        $this->assertNull($ability->entity_id);
    }
}
