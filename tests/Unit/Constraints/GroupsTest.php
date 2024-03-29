<?php

namespace Silber\Bouncer\Tests\Unit\Constraints;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Silber\Bouncer\Constraints\Constraint;
use Silber\Bouncer\Constraints\Group;
use Workbench\App\Models\Account;
use Workbench\App\Models\User;

class GroupsTest extends TestCase
{
    #[Test]
    public function named_and_constructor()
    {
        $group = Group::withAnd();

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('and', $group->logicalOperator());
    }

    #[Test]
    public function named_or_constructor()
    {
        $group = Group::withOr();

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('or', $group->logicalOperator());
    }

    #[Test]
    public function group_of_constraints_only_passes_if_all_constraints_pass_the_check()
    {
        $account = new Account([
            'name' => 'the-account',
            'active' => false,
        ]);

        $groupA = new Group([
            Constraint::where('name', 'the-account'),
            Constraint::where('active', false),
        ]);

        $groupB = new Group([
            Constraint::where('name', 'the-account'),
            Constraint::where('active', true),
        ]);

        $this->assertTrue($groupA->check($account, new User));
        $this->assertFalse($groupB->check($account, new User));
    }

    #[Test]
    public function group_of_ors_passes_if_any_constraint_passes_the_check()
    {
        $account = new Account([
            'name' => 'the-account',
            'active' => false,
        ]);

        $groupA = new Group([
            Constraint::orWhere('name', 'the-account'),
            Constraint::orWhere('active', true),
        ]);

        $groupB = new Group([
            Constraint::orWhere('name', 'a-different-account'),
            Constraint::orWhere('active', true),
        ]);

        $this->assertTrue($groupA->check($account, new User));
        $this->assertFalse($groupB->check($account, new User));
    }

    #[Test]
    public function group_can_be_serialized_and_deserialized()
    {
        $activeAccount = new Account([
            'name' => 'the-account',
            'active' => true,
        ]);

        $inactiveAccount = new Account([
            'name' => 'the-account',
            'active' => false,
        ]);

        $group = $this->serializeAndDeserializeGroup(new Group([
            Constraint::where('name', 'the-account'),
            Constraint::where('active', true),
        ]));

        $this->assertInstanceOf(Group::class, $group);
        $this->assertTrue($group->check($activeAccount, new User));
        $this->assertFalse($group->check($inactiveAccount, new User));
    }

    #[Test]
    public function group_can_be_added_to()
    {
        $activeAccount = new Account([
            'name' => 'account',
            'active' => true,
        ]);

        $inactiveAccount = new Account([
            'name' => 'account',
            'active' => false,
        ]);

        $group = (new Group)
            ->add(Constraint::where('name', 'account'))
            ->add(Constraint::where('active', true));

        $this->assertTrue($group->check($activeAccount, new User));
        $this->assertFalse($group->check($inactiveAccount, new User));
    }

    /**
     * Convert the given object to JSON, then back.
     *
     * @return \Silber\Bouncer\Constraints\Group
     */
    protected function serializeAndDeserializeGroup(Group $group)
    {
        $data = json_decode(json_encode($group->data()), true);

        return $data['class']::fromData($data['params']);
    }
}
