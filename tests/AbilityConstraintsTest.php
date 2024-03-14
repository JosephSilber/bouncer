<?php

namespace Silber\Bouncer\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Constraints\Group;
use Silber\Bouncer\Constraints\Constraint;
use Workbench\App\Models\User;
use Workbench\App\Models\Account;

class AbilityConstraintsTest extends BaseTestCase
{
    #[Test]
    public function can_get_empty_constraints()
    {
        $group = Ability::createForModel(Account::class, '*')->getConstraints();

        $this->assertInstanceOf(Group::class, $group);
    }

    #[Test]
    public function can_check_if_has_constraints()
    {
        $empty = Ability::makeForModel(Account::class, '*');

        $full = Ability::makeForModel(Account::class, '*')->setConstraints(
            Group::withAnd()->add(Constraint::where('active', true))
        );

        $this->assertFalse($empty->hasConstraints());
        $this->assertTrue($full->hasConstraints());
    }

    #[Test]
    public function can_set_and_get_constraints()
    {
        $ability = Ability::makeForModel(Account::class, '*')->setConstraints(
            new Group([
                Constraint::where('active', true)
            ])
        );

        $ability->save();

        $constraints = Ability::find($ability->id)->getConstraints();

        $this->assertInstanceOf(Group::class, $constraints);
        $this->assertTrue($constraints->check(new Account(['active' => true]), new User));
        $this->assertFalse($constraints->check(new Account(['active' => false]), new User));
    }
}
