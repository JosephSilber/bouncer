<?php

namespace Silber\Bouncer\Tests\Unit\Constraints;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Silber\Bouncer\Constraints\ColumnConstraint;
use Silber\Bouncer\Constraints\Constraint;
use Silber\Bouncer\Constraints\ValueConstraint;
use Workbench\App\Models\Account;
use Workbench\App\Models\User;

class ConstraintTest extends TestCase
{
    #[Test]
    public function value_constraint_implicit_equals()
    {
        $authority = new User();
        $activeAccount = new Account(['active' => true]);
        $inactiveAccount = new Account(['active' => false]);

        $constraint = Constraint::where('active', true);

        $this->assertTrue($constraint->check($activeAccount, $authority));
        $this->assertFalse($constraint->check($inactiveAccount, $authority));
    }

    #[Test]
    public function value_constraint_explicit_equals()
    {
        $authority = new User();
        $activeAccount = new Account(['active' => true]);
        $inactiveAccount = new Account(['active' => false]);

        $constraint = Constraint::where('active', '=', true);

        $this->assertTrue($constraint->check($activeAccount, $authority));
        $this->assertFalse($constraint->check($inactiveAccount, $authority));
    }

    #[Test]
    public function value_constraint_explicit_double_equals()
    {
        $authority = new User();
        $activeAccount = new Account(['active' => true]);
        $inactiveAccount = new Account(['active' => false]);

        $constraint = Constraint::where('active', '==', true);

        $this->assertTrue($constraint->check($activeAccount, $authority));
        $this->assertFalse($constraint->check($inactiveAccount, $authority));
    }

    #[Test]
    public function value_constraint_not_equals()
    {
        $authority = new User();
        $activeAccount = new Account(['active' => true]);
        $inactiveAccount = new Account(['active' => false]);

        $constraint = Constraint::where('active', '!=', false);

        $this->assertTrue($constraint->check($activeAccount, $authority));
        $this->assertFalse($constraint->check($inactiveAccount, $authority));
    }

    #[Test]
    public function value_constraint_greater_than()
    {
        $authority = new User();
        $forty = new User(['age' => 40]);
        $fortyOne = new User(['age' => 41]);

        $constraint = Constraint::where('age', '>', 40);

        $this->assertTrue($constraint->check($fortyOne, $authority));
        $this->assertFalse($constraint->check($forty, $authority));
    }

    #[Test]
    public function value_constraint_less_than()
    {
        $authority = new User();
        $thirtyNine = new User(['age' => 39]);
        $forty = new User(['age' => 40]);

        $constraint = Constraint::where('age', '<', 40);

        $this->assertTrue($constraint->check($thirtyNine, $authority));
        $this->assertFalse($constraint->check($forty, $authority));
    }

    #[Test]
    public function value_constraint_greater_than_or_equal()
    {
        $authority = new User();
        $minor = new User(['age' => 17]);
        $adult = new User(['age' => 18]);
        $senior = new User(['age' => 80]);

        $constraint = Constraint::where('age', '>=', 18);

        $this->assertTrue($constraint->check($adult, $authority));
        $this->assertTrue($constraint->check($senior, $authority));
        $this->assertFalse($constraint->check($minor, $authority));
    }

    #[Test]
    public function value_constraint_less_than_or_equal()
    {
        $authority = new User();
        $youngerTeen = new User(['age' => 18]);
        $olderTeen = new User(['age' => 19]);
        $adult = new User(['age' => 20]);

        $constraint = Constraint::where('age', '<=', 19);

        $this->assertTrue($constraint->check($youngerTeen, $authority));
        $this->assertTrue($constraint->check($olderTeen, $authority));
        $this->assertFalse($constraint->check($adult, $authority));
    }

    #[Test]
    public function column_constraint_implicit_equals()
    {
        $authority = new User(['age' => 1]);
        $one = new User(['age' => 1]);
        $two = new User(['age' => 2]);

        $constraint = Constraint::whereColumn('age', 'age');

        $this->assertTrue($constraint->check($one, $authority));
        $this->assertFalse($constraint->check($two, $authority));
    }

    #[Test]
    public function column_constraint_explicit_equals()
    {
        $authority = new User(['age' => 1]);
        $one = new User(['age' => 1]);
        $two = new User(['age' => 2]);

        $constraint = Constraint::whereColumn('age', '=', 'age');

        $this->assertTrue($constraint->check($one, $authority));
        $this->assertFalse($constraint->check($two, $authority));
    }

    #[Test]
    public function column_constraint_explicit_double_equals()
    {
        $authority = new User(['age' => 1]);
        $one = new User(['age' => 1]);
        $two = new User(['age' => 2]);

        $constraint = Constraint::whereColumn('age', '=', 'age');

        $this->assertTrue($constraint->check($one, $authority));
        $this->assertFalse($constraint->check($two, $authority));
    }

    #[Test]
    public function column_constraint_not_equals()
    {
        $authority = new User(['age' => 1]);
        $one = new User(['age' => 1]);
        $two = new User(['age' => 2]);

        $constraint = Constraint::whereColumn('age', '!=', 'age');

        $this->assertTrue($constraint->check($two, $authority));
        $this->assertFalse($constraint->check($one, $authority));
    }

    #[Test]
    public function column_constraint_greater_than()
    {
        $authority = new User(['age' => 18]);

        $younger = new User(['age' => 17]);
        $same = new User(['age' => 18]);
        $older = new User(['age' => 19]);

        $constraint = Constraint::whereColumn('age', '>', 'age');

        $this->assertTrue($constraint->check($older, $authority));
        $this->assertFalse($constraint->check($younger, $authority));
        $this->assertFalse($constraint->check($same, $authority));
    }

    #[Test]
    public function column_constraint_less_than()
    {
        $authority = new User(['age' => 18]);

        $younger = new User(['age' => 17]);
        $same = new User(['age' => 18]);
        $older = new User(['age' => 19]);

        $constraint = Constraint::whereColumn('age', '<', 'age');

        $this->assertTrue($constraint->check($younger, $authority));
        $this->assertFalse($constraint->check($older, $authority));
        $this->assertFalse($constraint->check($same, $authority));
    }

    #[Test]
    public function column_constraint_greater_than_or_equal()
    {
        $authority = new User(['age' => 18]);

        $younger = new User(['age' => 17]);
        $same = new User(['age' => 18]);
        $older = new User(['age' => 19]);

        $constraint = Constraint::whereColumn('age', '>=', 'age');

        $this->assertTrue($constraint->check($same, $authority));
        $this->assertTrue($constraint->check($older, $authority));
        $this->assertFalse($constraint->check($younger, $authority));
    }

    #[Test]
    public function column_constraint_less_than_or_equal()
    {
        $authority = new User(['age' => 18]);

        $younger = new User(['age' => 17]);
        $same = new User(['age' => 18]);
        $older = new User(['age' => 19]);

        $constraint = Constraint::whereColumn('age', '<=', 'age');

        $this->assertTrue($constraint->check($younger, $authority));
        $this->assertTrue($constraint->check($same, $authority));
        $this->assertFalse($constraint->check($older, $authority));
    }

    #[Test]
    public function value_constraint_can_be_properly_serialized_and_deserialized()
    {
        $authority = new User();
        $activeAccount = new Account(['active' => true]);
        $inactiveAccount = new Account(['active' => false]);

        $constraint = $this->serializeAndDeserializeConstraint(
            Constraint::where('active', true)
        );

        $this->assertInstanceOf(ValueConstraint::class, $constraint);
        $this->assertTrue($constraint->check($activeAccount, $authority));
        $this->assertFalse($constraint->check($inactiveAccount, $authority));
    }

    #[Test]
    public function column_constraint_can_be_properly_serialized_and_deserialized()
    {
        $authority = new User(['age' => 1]);
        $one = new User(['age' => 1]);
        $two = new User(['age' => 2]);

        $constraint = $this->serializeAndDeserializeConstraint(
            Constraint::whereColumn('age', 'age')
        );

        $this->assertInstanceOf(ColumnConstraint::class, $constraint);
        $this->assertTrue($constraint->check($one, $authority));
        $this->assertFalse($constraint->check($two, $authority));
    }

    /**
     * Convert the given object to JSON, then back.
     *
     * @param  \Silber\Bouncer\Constraints\Constraint  $group
     * @return \Silber\Bouncer\Constraints\Constraint
     */
    protected function serializeAndDeserializeConstraint(Constraint $constraint)
    {
        $data = json_decode(json_encode($constraint->data()), true);

        return $data['class']::fromData($data['params']);
    }
}
