<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;

class OwnershipTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_own_a_model_class($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->toOwn(Account::class);

        $account = Account::create(['user_id' => $user->id]);

        $this->assertTrue($bouncer->cannot('update', Account::class));
        $this->assertTrue($bouncer->can('update', $account));

        $account->user_id = 99;

        $this->assertTrue($bouncer->cannot('update', $account));

        $bouncer->allow($user)->to('update', $account);
        $bouncer->disallow($user)->toOwn(Account::class);

        $this->assertTrue($bouncer->can('update', $account));

        $bouncer->disallow($user)->to('update', $account);

        $this->assertTrue($bouncer->cannot('update', $account));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_own_a_model($provider)
    {
        list($bouncer, $user) = $provider();

        $account1 = Account::create(['user_id' => $user->id]);
        $account2 = Account::create(['user_id' => $user->id]);

        $bouncer->allow($user)->toOwn($account1);

        $this->assertTrue($bouncer->cannot('update', Account::class));
        $this->assertTrue($bouncer->cannot('update', $account2));
        $this->assertTrue($bouncer->can('update', $account1));

        $account1->user_id = 99;

        $this->assertTrue($bouncer->cannot('update', $account1));

        $bouncer->allow($user)->to('update', $account1);
        $bouncer->disallow($user)->toOwn($account1);

        $this->assertTrue($bouncer->can('update', $account1));

        $bouncer->disallow($user)->to('update', $account1);

        $this->assertTrue($bouncer->cannot('update', $account1));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_own_a_model_for_a_given_ability($provider)
    {
        list($bouncer, $user) = $provider();

        $account1 = Account::create(['user_id' => $user->id]);
        $account2 = Account::create(['user_id' => $user->id]);

        $bouncer->allow($user)->toOwn($account1)->to('update');
        $bouncer->allow($user)->toOwn($account2)->to(['view', 'update']);

        $this->assertTrue($bouncer->cannot('update', Account::class));
        $this->assertTrue($bouncer->can('update', $account1));
        $this->assertTrue($bouncer->cannot('delete', $account1));
        $this->assertTrue($bouncer->can('view', $account2));
        $this->assertTrue($bouncer->can('update', $account2));
        $this->assertTrue($bouncer->cannot('delete', $account2));

        $account1->user_id = 99;

        $this->assertTrue($bouncer->cannot('update', $account1));

        $bouncer->allow($user)->to('update', $account1);
        $bouncer->disallow($user)->toOwn($account1)->to('update');

        $this->assertTrue($bouncer->can('update', $account1));

        $bouncer->disallow($user)->to('update', $account1);

        $this->assertTrue($bouncer->cannot('update', $account1));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_own_everything($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->toOwnEverything();

        $account = Account::create(['user_id' => $user->id]);

        $this->assertTrue($bouncer->cannot('delete', Account::class));
        $this->assertTrue($bouncer->can('delete', $account));

        $account->user_id = 99;

        $this->assertTrue($bouncer->cannot('delete', $account));

        $account->user_id = $user->id;

        $bouncer->disallow($user)->toOwnEverything();

        $this->assertTrue($bouncer->cannot('delete', $account));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_own_everything_for_a_given_ability($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->allow($user)->toOwnEverything()->to('view');

        $account = Account::create(['user_id' => $user->id]);

        $this->assertTrue($bouncer->cannot('delete', Account::class));
        $this->assertTrue($bouncer->cannot('delete', $account));
        $this->assertTrue($bouncer->can('view', $account));

        $account->user_id = 99;

        $this->assertTrue($bouncer->cannot('view', $account));

        $account->user_id = $user->id;

        $bouncer->disallow($user)->toOwnEverything()->to('view');

        $this->assertTrue($bouncer->cannot('view', $account));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_use_custom_ownership_attribute($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->ownedVia('userId');

        $account = Account::create()->fill(['userId' => $user->id]);

        $bouncer->allow($user)->toOwn(Account::class);

        $this->assertTrue($bouncer->can('view', $account));

        Models::reset();
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function can_use_custom_ownership_attribute_for_model_type($provider)
    {
        list($bouncer, $user) = $provider();

        $bouncer->ownedVia(Account::class, 'userId');

        $account = Account::create()->fill(['userId' => $user->id]);

        $bouncer->allow($user)->toOwn(Account::class);

        $this->assertTrue($bouncer->can('view', $account));

        Models::reset();
    }
}
