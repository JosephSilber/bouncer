<?php

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;

class OwnershipTest extends BaseTestCase
{
    public function test_can_own_a_model_class()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->toOwn(Account::class);

        $account = Account::create(['user_id' => $user->id]);

        $this->assertTrue($bouncer->denies('update', Account::class));
        $this->assertTrue($bouncer->allows('update', $account));

        $account->user_id = 99;

        $this->assertTrue($bouncer->denies('update', $account));

        $bouncer->allow($user)->to('update', $account);
        $bouncer->disallow($user)->toOwn(Account::class);

        $this->assertTrue($bouncer->allows('update', $account));

        $bouncer->disallow($user)->to('update', $account);

        $this->assertTrue($bouncer->denies('update', $account));
    }

    public function test_can_own_a_model()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $account1 = Account::create(['user_id' => $user->id]);
        $account2 = Account::create(['user_id' => $user->id]);

        $bouncer->allow($user)->toOwn($account1);

        $this->assertTrue($bouncer->denies('update', Account::class));
        $this->assertTrue($bouncer->denies('update', $account2));
        $this->assertTrue($bouncer->allows('update', $account1));

        $account1->user_id = 99;

        $this->assertTrue($bouncer->denies('update', $account1));

        $bouncer->allow($user)->to('update', $account1);
        $bouncer->disallow($user)->toOwn($account1);

        $this->assertTrue($bouncer->allows('update', $account1));

        $bouncer->disallow($user)->to('update', $account1);

        $this->assertTrue($bouncer->denies('update', $account1));
    }

    public function test_can_own_a_model_class_for_single_ability()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->toOwn(Account::class, 'update');

        $account = Account::create(['user_id' => $user->id]);

        $this->assertTrue($bouncer->denies('delete', $account));
        $this->assertTrue($bouncer->allows('update', $account));

        $bouncer->allow($user)->to('update', $account);
        $bouncer->disallow($user)->toOwn(Account::class, 'update');

        $this->assertTrue($bouncer->allows('update', $account));

        $bouncer->disallow($user)->to('update', $account);

        $this->assertTrue($bouncer->denies('update', $account));
    }

    public function test_can_own_a_model_class_for_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->toOwn(Account::class, ['view', 'update']);

        $account = Account::create(['user_id' => $user->id]);

        $this->assertTrue($bouncer->denies('delete', $account));
        $this->assertTrue($bouncer->allows('update', $account));
        $this->assertTrue($bouncer->allows('view', $account));

        $bouncer->disallow($user)->toOwn(Account::class, ['view', 'update']);

        $this->assertTrue($bouncer->denies('update', $account));
        $this->assertTrue($bouncer->denies('view', $account));
    }

    public function test_can_own_everything()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->toOwnEverything();

        $account = Account::create(['user_id' => $user->id]);

        $this->assertTrue($bouncer->denies('delete', Account::class));
        $this->assertTrue($bouncer->allows('delete', $account));

        $account->user_id = 99;

        $this->assertTrue($bouncer->denies('delete', $account));

        $account->user_id = $user->id;

        $bouncer->disallow($user)->toOwnEverything();

        $this->assertTrue($bouncer->denies('delete', $account));
    }

    public function test_can_own_everything_for_a_single_ability()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->toOwnEverything('update');

        $account = Account::create(['user_id' => $user->id]);

        $this->assertTrue($bouncer->denies('update', Account::class));
        $this->assertTrue($bouncer->denies('delete', $account));
        $this->assertTrue($bouncer->allows('update', $account));

        $account->user_id = 99;

        $this->assertTrue($bouncer->denies('update', $account));

        $account->user_id = $user->id;

        $bouncer->disallow($user)->toOwnEverything('update');

        $this->assertTrue($bouncer->denies('update', $account));
    }

    public function test_can_own_everything_for_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->toOwnEverything(['view', 'update']);

        $account = Account::create(['user_id' => $user->id]);

        $this->assertTrue($bouncer->denies('update', Account::class));
        $this->assertTrue($bouncer->denies('delete', $account));
        $this->assertTrue($bouncer->allows('update', $account));
        $this->assertTrue($bouncer->allows('view', $account));

        $account->user_id = 99;

        $this->assertTrue($bouncer->denies('update', $account));
        $this->assertTrue($bouncer->denies('view', $account));

        $account->user_id = $user->id;

        $bouncer->disallow($user)->toOwnEverything(['view', 'update']);

        $this->assertTrue($bouncer->denies('update', $account));
        $this->assertTrue($bouncer->denies('view', $account));
    }

    public function test_can_use_custom_ownership_attribute()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->ownedVia('userId');

        $account = Account::create()->fill(['userId' => $user->id]);

        $bouncer->allow($user)->toOwn(Account::class);

        $this->assertTrue($bouncer->allows('view', $account));

        Models::reset();
    }
}
