<?php

class MultipleAbilitiesTest extends BaseTestCase
{
    public function test_allowing_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));
    }

    public function test_allowing_multiple_model_abilities()
    {
        $bouncer = $this->bouncer($user1 = User::create())->dontCache();
        $user2 = User::create();

        $bouncer->allow($user1)->to(['edit', 'delete'], $user1);

        $this->assertTrue($bouncer->allows('edit', $user1));
        $this->assertTrue($bouncer->allows('delete', $user1));
        $this->assertTrue($bouncer->denies('edit', $user2));
        $this->assertTrue($bouncer->denies('delete', $user2));
    }

    public function test_allowing_multiple_blanket_model_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', User::class));
    }

    public function test_allowing_an_ability_on_multiple_models()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1)->dontCache();

        $bouncer->allow($user1)->to('delete', [Account::class, $user1]);

        $this->assertTrue($bouncer->allows('delete', Account::class));
        $this->assertTrue($bouncer->allows('delete', $user1));
        $this->assertTrue($bouncer->denies('delete', $user2));
        $this->assertTrue($bouncer->denies('delete', User::class));
    }

    public function test_allowing_multiple_abilities_on_multiple_models()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1)->dontCache();

        $bouncer->allow($user1)->to(['update', 'delete'], [Account::class, $user1]);

        $this->assertTrue($bouncer->allows('update', Account::class));
        $this->assertTrue($bouncer->allows('delete', Account::class));
        $this->assertTrue($bouncer->allows('update', $user1));
        $this->assertTrue($bouncer->denies('update', $user2));
        $this->assertTrue($bouncer->denies('update', User::class));
    }

    public function test_allowing_multiple_abilities_via_a_map()
    {
        $bouncer = $this->bouncer($user1 = User::create())->dontCache();
        $user2 = User::create();

        $account1 = Account::create();
        $account2 = Account::create();

        $bouncer->allow($user1)->to([
            'edit'   => User::class,
            'delete' => $user1,
            'view'   => Account::class,
            'update' => $account1,
            'access-dashboard',
        ]);

        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->denies('view', User::class));
        $this->assertTrue($bouncer->allows('delete', $user1));
        $this->assertTrue($bouncer->denies('delete', $user2));

        $this->assertTrue($bouncer->allows('view', Account::class));
        $this->assertTrue($bouncer->denies('update', Account::class));
        $this->assertTrue($bouncer->allows('update', $account1));
        $this->assertTrue($bouncer->denies('update', $account2));

        $this->assertTrue($bouncer->allows('access-dashboard'));
    }

    public function test_disallowing_multiple_abilties()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);
        $bouncer->disallow($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('delete'));
    }

    public function test_disallowing_multiple_model_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['view', 'edit', 'delete'], $user);
        $bouncer->disallow($user)->to(['edit', 'delete'], $user);

        $this->assertTrue($bouncer->allows('view', $user));
        $this->assertTrue($bouncer->denies('edit', $user));
        $this->assertTrue($bouncer->denies('delete', $user));
    }

    public function test_disallowing_multiple_blanket_model_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete'], User::class);
        $bouncer->disallow($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->denies('delete', User::class));
    }

    public function test_disallowing_multiple_abilities_via_a_map()
    {
        $bouncer = $this->bouncer($user1 = User::create())->dontCache();
        $user2 = User::create();

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

        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', $user1));
        $this->assertTrue($bouncer->allows('view', $account1));
        $this->assertTrue($bouncer->denies('update', $account1));
    }

    public function test_forbidding_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);
        $bouncer->forbid($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('delete'));
    }

    public function test_forbidding_multiple_model_abilities()
    {
        $bouncer = $this->bouncer($user1 = User::create())->dontCache();
        $user2 = User::create();

        $bouncer->allow($user1)->to(['view', 'edit', 'delete']);
        $bouncer->allow($user1)->to(['view', 'edit', 'delete'], $user1);
        $bouncer->allow($user1)->to(['view', 'edit', 'delete'], $user2);
        $bouncer->forbid($user1)->to(['edit', 'delete'], $user1);

        $this->assertTrue($bouncer->allows('view'));
        $this->assertTrue($bouncer->allows('edit'));

        $this->assertTrue($bouncer->allows('view', $user1));
        $this->assertTrue($bouncer->denies('edit', $user1));
        $this->assertTrue($bouncer->denies('delete', $user1));
        $this->assertTrue($bouncer->allows('edit', $user2));
        $this->assertTrue($bouncer->allows('delete', $user2));
    }

    public function test_forbidding_multiple_blanket_model_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);
        $bouncer->allow($user)->to(['edit', 'delete'], Account::class);
        $bouncer->allow($user)->to(['view', 'edit', 'delete'], User::class);
        $bouncer->forbid($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));

        $this->assertTrue($bouncer->allows('edit', Account::class));
        $this->assertTrue($bouncer->allows('delete', Account::class));

        $this->assertTrue($bouncer->allows('view', User::class));
        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->denies('delete', User::class));
    }

    public function test_forbidding_multiple_abilities_via_a_map()
    {
        $bouncer = $this->bouncer($user1 = User::create())->dontCache();
        $user2 = User::create();

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

        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', $user1));
        $this->assertTrue($bouncer->allows('view', $account1));
        $this->assertTrue($bouncer->denies('update', $account1));
    }

    public function test_unforbidding_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['view', 'edit', 'delete']);
        $bouncer->forbid($user)->to(['view', 'edit', 'delete']);
        $bouncer->unforbid($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->denies('view'));
        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));
    }

    public function test_unforbidding_multiple_model_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['view', 'edit', 'delete'], $user);
        $bouncer->forbid($user)->to(['view', 'edit', 'delete'], $user);
        $bouncer->unforbid($user)->to(['edit', 'delete'], $user);

        $this->assertTrue($bouncer->denies('view', $user));
        $this->assertTrue($bouncer->allows('edit', $user));
        $this->assertTrue($bouncer->allows('delete', $user));
    }

    public function test_unforbidding_multiple_blanket_model_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['view', 'edit', 'delete'], User::class);
        $bouncer->forbid($user)->to(['view', 'edit', 'delete'], User::class);
        $bouncer->unforbid($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->denies('view', User::class));
        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', User::class));
    }

    public function test_unforbidding_multiple_abilities_via_a_map()
    {
        $bouncer = $this->bouncer($user1 = User::create())->dontCache();
        $user2 = User::create();

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

        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->denies('delete', $user1));
        $this->assertTrue($bouncer->denies('view', $account1));
        $this->assertTrue($bouncer->allows('update', $account1));
    }
}
