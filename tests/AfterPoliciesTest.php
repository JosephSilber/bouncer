<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Bouncer;
use Illuminate\Auth\Access\Gate;
use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class AfterPoliciesTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function fails_auth_check_when_policy_fails_even_if_bouncer_allows($provider)
    {
        list($bouncer, $user) = $provider();

        $this->setUpWithPolicy($bouncer);

        $account = Account::create(['name' => 'false']);

        $bouncer->allow($user)->to('view', $account);

        $this->assertTrue($bouncer->cannot('view', $account));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function passes_auth_check_when_policy_passes_even_if_bouncer_fails($provider)
    {
        list($bouncer, $user) = $provider();

        $this->setUpWithPolicy($bouncer);

        $account = Account::create(['name' => 'true']);

        $bouncer->forbid($user)->to('view', $account);

        $this->assertTrue($bouncer->can('view', $account));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function passes_auth_check_when_bouncer_allows($provider)
    {
        list($bouncer, $user) = $provider();

        $this->setUpWithPolicy($bouncer);

        $account = Account::create(['name' => 'ignored by policy']);

        $bouncer->allow($user)->to('view', $account);

        $this->assertTrue($bouncer->can('view', $account));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function fails_auth_check_when_bouncer_does_not_allow($provider)
    {
        list($bouncer, $user) = $provider();

        $this->setUpWithPolicy($bouncer);

        $account = Account::create(['name' => 'ignored by policy']);

        $this->assertTrue($bouncer->cannot('view', $account));
    }

    /**
     * Set up the given Bouncer instance with the test policy.
     *
     * @param \Silber\Buoncer\Bouncer  $bouncer
     */
    protected function setUpWithPolicy(Bouncer $bouncer)
    {
        $bouncer->runAfterPolicies();

        $bouncer->gate()->policy(Account::class, AccountPolicyForAfter::class);
    }
}

class AccountPolicyForAfter
{
    public function view($user, $account)
    {
        if ($account->name == 'true') {
            return true;
        }

        if ($account->name == 'false') {
            return false;
        }
    }
}
