<?php

namespace Silber\Bouncer\Tests;

class MultipleAbilitiesTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function allowing_multiple_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->can('edit'));
        $this->assertTrue($bouncer->can('delete'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function allowing_multiple_model_abilities($provider)
    {
        list($bouncer, $user1, $user2) = $provider(2);

        $bouncer->allow($user1)->to(['edit', 'delete'], $user1);

        $this->assertTrue($bouncer->can('edit', $user1));
        $this->assertTrue($bouncer->can('delete', $user1));
        $this->assertTrue($bouncer->cannot('edit', $user2));
        $this->assertTrue($bouncer->cannot('delete', $user2));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function allowing_multiple_blanket_model_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->can('edit', User::class));
        $this->assertTrue($bouncer->can('delete', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function allowing_an_ability_on_multiple_models($provider)
    {
        list($bouncer, $user1, $user2) = $provider(2);

        $bouncer->allow($user1)->to('delete', [Account::class, $user1]);

        $this->assertTrue($bouncer->can('delete', Account::class));
        $this->assertTrue($bouncer->can('delete', $user1));
        $this->assertTrue($bouncer->cannot('delete', $user2));
        $this->assertTrue($bouncer->cannot('delete', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function allowing_multiple_abilities_on_multiple_models($provider)
    {
        list($bouncer, $user1, $user2) = $provider(2);

        $bouncer->allow($user1)->to(['update', 'delete'], [Account::class, $user1]);

        $this->assertTrue($bouncer->can('update', Account::class));
        $this->assertTrue($bouncer->can('delete', Account::class));
        $this->assertTrue($bouncer->can('update', $user1));
        $this->assertTrue($bouncer->cannot('update', $user2));
        $this->assertTrue($bouncer->cannot('update', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function allowing_multiple_abilities_via_a_map($provider)
    {
        list($bouncer, $user1, $user2) = $provider(2);

        $account1 = Account::create();
        $account2 = Account::create();

        $bouncer->allow($user1)->to([
            'edit'   => User::class,
            'delete' => $user1,
            'view'   => Account::class,
            'update' => $account1,
            'access-dashboard',
        ]);

        $this->assertTrue($bouncer->can('edit', User::class));
        $this->assertTrue($bouncer->cannot('view', User::class));
        $this->assertTrue($bouncer->can('delete', $user1));
        $this->assertTrue($bouncer->cannot('delete', $user2));

        $this->assertTrue($bouncer->can('view', Account::class));
        $this->assertTrue($bouncer->cannot('update', Account::class));
        $this->assertTrue($bouncer->can('update', $account1));
        $this->assertTrue($bouncer->cannot('update', $account2));

        $this->assertTrue($bouncer->can('access-dashboard'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function disallowing_multiple_abilties($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to(['edit', 'delete']);
        $bouncer->disallow($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->cannot('edit'));
        $this->assertTrue($bouncer->cannot('delete'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function disallowing_multiple_model_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to(['view', 'edit', 'delete'], $user);
        $bouncer->disallow($user)->to(['edit', 'delete'], $user);

        $this->assertTrue($bouncer->can('view', $user));
        $this->assertTrue($bouncer->cannot('edit', $user));
        $this->assertTrue($bouncer->cannot('delete', $user));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function disallowing_multiple_blanket_model_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to(['edit', 'delete'], User::class);
        $bouncer->disallow($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->cannot('delete', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function disallowing_multiple_abilities_via_a_map($provider)
    {
        list($bouncer, $user1, $user2) = $provider(2);

        $account1 = Account::create();
        $account2 = Account::create();

        $bouncer->allow($user1)->to([
            'edit'   => User::class,
            'delete' => $user1,
            'view'   => Account::class,
            'update' => $account1,
        ]);

        $bouncer->disallow($user1)->to([
            'edit'   => User::class,
            'update' => $account1,
        ]);

        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->can('delete', $user1));
        $this->assertTrue($bouncer->can('view', $account1));
        $this->assertTrue($bouncer->cannot('update', $account1));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_multiple_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to(['edit', 'delete']);
        $bouncer->forbid($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->cannot('edit'));
        $this->assertTrue($bouncer->cannot('delete'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_multiple_model_abilities($provider)
    {
        list($bouncer, $user1, $user2) = $provider(2);

        $bouncer->allow($user1)->to(['view', 'edit', 'delete']);
        $bouncer->allow($user1)->to(['view', 'edit', 'delete'], $user1);
        $bouncer->allow($user1)->to(['view', 'edit', 'delete'], $user2);
        $bouncer->forbid($user1)->to(['edit', 'delete'], $user1);

        $this->assertTrue($bouncer->can('view'));
        $this->assertTrue($bouncer->can('edit'));

        $this->assertTrue($bouncer->can('view', $user1));
        $this->assertTrue($bouncer->cannot('edit', $user1));
        $this->assertTrue($bouncer->cannot('delete', $user1));
        $this->assertTrue($bouncer->can('edit', $user2));
        $this->assertTrue($bouncer->can('delete', $user2));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_multiple_blanket_model_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to(['edit', 'delete']);
        $bouncer->allow($user)->to(['edit', 'delete'], Account::class);
        $bouncer->allow($user)->to(['view', 'edit', 'delete'], User::class);
        $bouncer->forbid($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->can('edit'));
        $this->assertTrue($bouncer->can('delete'));

        $this->assertTrue($bouncer->can('edit', Account::class));
        $this->assertTrue($bouncer->can('delete', Account::class));

        $this->assertTrue($bouncer->can('view', User::class));
        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->cannot('delete', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function forbidding_multiple_abilities_via_a_map($provider)
    {
        list($bouncer, $user1, $user2) = $provider(2);

        $account1 = Account::create();
        $account2 = Account::create();

        $bouncer->allow($user1)->to([
            'edit'   => User::class,
            'delete' => $user1,
            'view'   => Account::class,
            'update' => $account1,
        ]);

        $bouncer->forbid($user1)->to([
            'edit'   => User::class,
            'update' => $account1,
        ]);

        $this->assertTrue($bouncer->cannot('edit', User::class));
        $this->assertTrue($bouncer->can('delete', $user1));
        $this->assertTrue($bouncer->can('view', $account1));
        $this->assertTrue($bouncer->cannot('update', $account1));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function unforbidding_multiple_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to(['view', 'edit', 'delete']);
        $bouncer->forbid($user)->to(['view', 'edit', 'delete']);
        $bouncer->unforbid($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->cannot('view'));
        $this->assertTrue($bouncer->can('edit'));
        $this->assertTrue($bouncer->can('delete'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function unforbidding_multiple_model_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to(['view', 'edit', 'delete'], $user);
        $bouncer->forbid($user)->to(['view', 'edit', 'delete'], $user);
        $bouncer->unforbid($user)->to(['edit', 'delete'], $user);

        $this->assertTrue($bouncer->cannot('view', $user));
        $this->assertTrue($bouncer->can('edit', $user));
        $this->assertTrue($bouncer->can('delete', $user));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function unforbidding_multiple_blanket_model_abilities($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->to(['view', 'edit', 'delete'], User::class);
        $bouncer->forbid($user)->to(['view', 'edit', 'delete'], User::class);
        $bouncer->unforbid($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->cannot('view', User::class));
        $this->assertTrue($bouncer->can('edit', User::class));
        $this->assertTrue($bouncer->can('delete', User::class));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function unforbidding_multiple_abilities_via_a_map($provider)
    {
        list($bouncer, $user1, $user2) = $provider(2);

        $account1 = Account::create();
        $account2 = Account::create();

        $bouncer->allow($user1)->to([
            'edit'   => User::class,
            'delete' => $user1,
            'view'   => Account::class,
            'update' => $account1,
        ]);

        $bouncer->forbid($user1)->to([
            'edit'   => User::class,
            'delete' => $user1,
            'view'   => Account::class,
            'update' => $account1,
        ]);

        $bouncer->unforbid($user1)->to([
            'edit'   => User::class,
            'update' => $account1,
        ]);

        $this->assertTrue($bouncer->can('edit', User::class));
        $this->assertTrue($bouncer->cannot('delete', $user1));
        $this->assertTrue($bouncer->cannot('view', $account1));
        $this->assertTrue($bouncer->can('update', $account1));
    }
}
