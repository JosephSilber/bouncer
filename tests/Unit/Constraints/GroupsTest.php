<?php

namespace Tests\Unit;

use Account, User;
use PHPUnit\Framework\TestCase;
use Silber\Bouncer\Constraints\Group;
use Silber\Bouncer\Constraints\OrGroup;
use Silber\Bouncer\Constraints\AndGroup;
use Silber\Bouncer\Constraints\Constraint;

class GroupsTest extends TestCase
{
    /**
     * @test
     */
    function and_group_only_passes_if_all_constraints_pass_the_check()
    {
        $account = new Account([
            'name' => 'the-account',
            'active' => false,
        ]);

        $groupA = new AndGroup([
            Constraint::where('name', 'the-account'),
            Constraint::where('active', false),
        ]);

        $groupB = new AndGroup([
            Constraint::where('name', 'the-account'),
            Constraint::where('active', true),
        ]);

        $this->assertTrue($groupA->check($account, new User));
        $this->assertFalse($groupB->check($account, new User));
    }

    /**
     * @test
     */
    function or_group_passes_if_any_constraint_passes_the_check()
    {
        $account = new Account([
            'name' => 'the-account',
            'active' => false,
        ]);

        $groupA = new OrGroup([
            Constraint::where('name', 'the-account'),
            Constraint::where('active', true),
        ]);

        $groupB = new OrGroup([
            Constraint::where('name', 'a-different-account'),
            Constraint::where('active', true),
        ]);

        $this->assertTrue($groupA->check($account, new User));
        $this->assertFalse($groupB->check($account, new User));
    }

    /**
     * @test
     */
    function and_group_can_be_serialized_and_deserialized()
    {
        $activeAccount = new Account([
            'name' => 'the-account',
            'active' => true,
        ]);

        $inactiveAccount = new Account([
            'name' => 'the-account',
            'active' => false,
        ]);

        $group = $this->serializeAndDeserializeGroup(new AndGroup([
            Constraint::where('name', 'the-account'),
            Constraint::where('active', true),
        ]));

        $this->assertInstanceOf(AndGroup::class, $group);
        $this->assertTrue($group->check($activeAccount, new User));
        $this->assertFalse($group->check($inactiveAccount, new User));
    }

    /**
     * @test
     */
    function or_group_can_be_serialized_and_deserialized()
    {
        $theAccount = new Account([
            'name' => 'the-account',
            'active' => false,
        ]);

        $anotherAccount = new Account([
            'name' => 'another-account',
            'active' => false,
        ]);

        $group = $this->serializeAndDeserializeGroup(new OrGroup([
            Constraint::where('name', 'the-account'),
            Constraint::where('active', true),
        ]));

        $this->assertInstanceOf(OrGroup::class, $group);
        $this->assertTrue($group->check($theAccount, new User));
        $this->assertFalse($group->check($anotherAccount, new User));
    }

    /**
     * @test
     */
    function and_group_can_be_added_to()
    {
        $activeAccount = new Account([
            'name' => 'account',
            'active' => true,
        ]);

        $inactiveAccount = new Account([
            'name' => 'account',
            'active' => false,
        ]);

        $group = Group::ofType('and')
            ->add(Constraint::where('name', 'account'))
            ->add(Constraint::where('active', true));

        $this->assertTrue($group->check($activeAccount, new User));
        $this->assertFalse($group->check($inactiveAccount, new User));
    }

    /**
     * @test
     */
    function or_group_can_be_added_to()
    {
        $activeAccount = new Account([
            'name' => 'another-account',
            'active' => true,
        ]);

        $inactiveAccount = new Account([
            'name' => 'another-account',
            'active' => false,
        ]);

        $group = Group::ofType('or')
            ->add(Constraint::where('name', 'the-account'))
            ->add(Constraint::where('active', true));

        $this->assertTrue($group->check($activeAccount, new User));
        $this->assertFalse($group->check($inactiveAccount, new User));
    }

    /**
     * @test
     */
    function and_group_can_be_checked_for_empty()
    {
        $this->assertTrue((new AndGroup([]))->isEmpty());
        $this->assertFalse((new AndGroup([
            Constraint::where('active', true),
        ]))->isEmpty());
    }

    /**
     * @test
     */
    function or_group_can_be_checked_for_empty()
    {
        $this->assertTrue((new OrGroup([]))->isEmpty());
        $this->assertFalse((new OrGroup([
            Constraint::where('active', true),
        ]))->isEmpty());
    }

    /**
     * @test
     */
    function and_group_can_be_checked_for_its_type()
    {
        $this->assertTrue(Group::ofType('and')->isOfType('and'));
        $this->assertFalse(Group::ofType('and')->isOfType('or'));
    }

    /**
     * @test
     */
    function or_group_can_be_checked_for_its_type()
    {
        $this->assertTrue(Group::ofType('or')->isOfType('or'));
        $this->assertFalse(Group::ofType('or')->isOfType('and'));
    }

    /**
     * Convert the given object to JSON, then back.
     *
     * @param  \Silber\Bouncer\Constraints\Group  $group
     * @return \Silber\Bouncer\Constraints\Group
     */
    protected function serializeAndDeserializeGroup(Group $group)
    {
        $data = json_decode(json_encode($group->data()), true);

        return $data['class']::fromData($data['params']);
    }
}
