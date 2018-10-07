<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Database\Ability;
use Silber\Bouncer\Constraints\Group;
use Silber\Bouncer\Constraints\Constraint;

class AbilityConstraintsTest extends BaseTestCase
{
    /**
     * @test
     */
    function can_get_empty_constraints()
    {
        $group = Ability::createForModel(Account::class, '*')->getConstraints();

        $this->assertInstanceOf(Group::class, $group);
    }

    /**
     * @test
     */
    function can_check_if_has_constraints()
    {
        $empty = Ability::makeForModel(Account::class, '*');

        $full = Ability::makeForModel(Account::class, '*')->setConstraints(
            Group::withAnd()->add(Constraint::where('active', true))
        );

        $this->assertFalse($empty->hasConstraints());
        $this->assertTrue($full->hasConstraints());
    }

    /**
     * @test
     */
    function can_set_and_get_constraints()
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
