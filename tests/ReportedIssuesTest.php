<?php

namespace Silber\Bouncer\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

use Workbench\App\Models\User;
use Workbench\App\Models\Account;

class ReportedIssuesTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    /**
     * @see https://github.com/JosephSilber/bouncer/pull/589
     */
    #[Test]
    #[DataProvider('bouncerProvider')]
    function forbid_an_ability_on_everything_with_zero_id($provider)
    {
        [$bouncer, $user1, $user2, $user3] = $provider(3);

        $user2->setAttribute($user2->getKeyName(), 0);

        $bouncer->allow($user1)->everything();
        $bouncer->forbid($user1)->to('edit', $user2);

        $this->assertTrue($bouncer->cannot('edit', $user2));
        $this->assertTrue($bouncer->can('edit', $user3));
    }
}
