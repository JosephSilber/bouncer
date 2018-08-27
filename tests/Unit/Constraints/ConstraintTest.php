<?php

namespace Tests\Unit;

use Account, User;
use PHPUnit\Framework\TestCase;
use Silber\Bouncer\Constraints\Constraint;

class ConstraintTest extends TestCase
{
    /**
     * @test
     */
    function value_constraint_equals()
    {
        $authority = new User();
        $activeAccount = new Account(['active' => true]);
        $inactiveAccount = new Account(['active' => false]);

        $constraint = Constraint::forWhere('active', true);

        $this->assertTrue($constraint->check($activeAccount, $authority));
        $this->assertFalse($constraint->check($inactiveAccount, $authority));
    }

    /**
     * @test
     */
    function value_constraint_not_equals()
    {
        $authority = new User();
        $activeAccount = new Account(['active' => true]);
        $inactiveAccount = new Account(['active' => false]);

        $constraint = Constraint::forWhere('active', '!=', false);

        $this->assertTrue($constraint->check($activeAccount, $authority));
        $this->assertFalse($constraint->check($inactiveAccount, $authority));
    }

    /**
     * @test
     */
    function value_constraint_greater_than()
    {
        $authority = new User();
        $forty = new User(['age' => 40]);
        $fortyOne = new User(['age' => 41]);

        $constraint = Constraint::forWhere('age', '>', 40);

        $this->assertTrue($constraint->check($fortyOne, $authority));
        $this->assertFalse($constraint->check($forty, $authority));
    }

    /**
     * @test
     */
    function value_constraint_less_than()
    {
        $authority = new User();
        $thirtyNine = new User(['age' => 39]);
        $forty = new User(['age' => 40]);

        $constraint = Constraint::forWhere('age', '<', 40);

        $this->assertTrue($constraint->check($thirtyNine, $authority));
        $this->assertFalse($constraint->check($forty, $authority));
    }

    /**
     * @test
     */
    function value_constraint_greater_than_or_equal()
    {
        $authority = new User();
        $minor = new User(['age' => 17]);
        $adult = new User(['age' => 18]);
        $senior = new User(['age' => 80]);

        $constraint = Constraint::forWhere('age', '>=', 18);

        $this->assertTrue($constraint->check($adult, $authority));
        $this->assertTrue($constraint->check($senior, $authority));
        $this->assertFalse($constraint->check($minor, $authority));
    }

    /**
     * @test
     */
    function value_constraint_less_than_or_equal()
    {
        $authority = new User();
        $youngerTeen = new User(['age' => 18]);
        $olderTeen = new User(['age' => 19]);
        $adult = new User(['age' => 20]);

        $constraint = Constraint::forWhere('age', '<=', 19);

        $this->assertTrue($constraint->check($youngerTeen, $authority));
        $this->assertTrue($constraint->check($olderTeen, $authority));
        $this->assertFalse($constraint->check($adult, $authority));
    }

    /**
     * @test
     */
    function column_constraint_equals()
    {
        $authority = new User(['age' => 1]);
        $one = new User(['age' => 1]);
        $two = new User(['age' => 2]);

        $constraint = Constraint::forWhereColumn('age', 'age');

        $this->assertTrue($constraint->check($one, $authority));
        $this->assertFalse($constraint->check($two, $authority));
    }

    /**
     * @test
     */
    function column_constraint_not_equals()
    {
        $authority = new User(['age' => 1]);
        $one = new User(['age' => 1]);
        $two = new User(['age' => 2]);

        $constraint = Constraint::forWhereColumn('age', '!=', 'age');

        $this->assertTrue($constraint->check($two, $authority));
        $this->assertFalse($constraint->check($one, $authority));
    }

    /**
     * @test
     */
    function column_constraint_greater_than()
    {
        $authority = new User(['age' => 18]);

        $younger = new User(['age' => 17]);
        $same = new User(['age' => 18]);
        $older = new User(['age' => 19]);

        $constraint = Constraint::forWhereColumn('age', '>', 'age');

        $this->assertTrue($constraint->check($older, $authority));
        $this->assertFalse($constraint->check($younger, $authority));
        $this->assertFalse($constraint->check($same, $authority));
    }

    /**
     * @test
     */
    function column_constraint_less_than()
    {
        $authority = new User(['age' => 18]);

        $younger = new User(['age' => 17]);
        $same = new User(['age' => 18]);
        $older = new User(['age' => 19]);

        $constraint = Constraint::forWhereColumn('age', '<', 'age');

        $this->assertTrue($constraint->check($younger, $authority));
        $this->assertFalse($constraint->check($older, $authority));
        $this->assertFalse($constraint->check($same, $authority));
    }

    /**
     * @test
     */
    function column_constraint_greater_than_or_equal()
    {
        $authority = new User(['age' => 18]);

        $younger = new User(['age' => 17]);
        $same = new User(['age' => 18]);
        $older = new User(['age' => 19]);

        $constraint = Constraint::forWhereColumn('age', '>=', 'age');

        $this->assertTrue($constraint->check($same, $authority));
        $this->assertTrue($constraint->check($older, $authority));
        $this->assertFalse($constraint->check($younger, $authority));
    }

    /**
     * @test
     */
    function column_constraint_less_than_or_equal()
    {
        $authority = new User(['age' => 18]);

        $younger = new User(['age' => 17]);
        $same = new User(['age' => 18]);
        $older = new User(['age' => 19]);

        $constraint = Constraint::forWhereColumn('age', '<=', 'age');

        $this->assertTrue($constraint->check($younger, $authority));
        $this->assertTrue($constraint->check($same, $authority));
        $this->assertFalse($constraint->check($older, $authority));
    }
}
