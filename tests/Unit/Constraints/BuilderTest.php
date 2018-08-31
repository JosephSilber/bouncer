<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Silber\Bouncer\Constraints\Group;
use Silber\Bouncer\Constraints\Builder;
use Silber\Bouncer\Constraints\Constraint;

class BuilderTest extends TestCase
{
    /**
     * @test
     */
    function building_without_constraints_returns_null()
    {
        $this->assertNull((new Builder())->build());
    }

    /**
     * @test
     */
    function a_single_where_returns_a_single_constraint()
    {
        $constraint = Builder::make()->where('active', false)->build();

        $this->assertTrue($constraint->equals(Constraint::where('active', false)));
    }

    /**
     * @test
     */
    function a_single_where_column_returns_a_single_column_constraint()
    {
        $builder = Builder::make()->whereColumn('team_id', 'team_id');

        $expected = Constraint::whereColumn('team_id', 'team_id');

        $this->assertTrue($expected->equals($builder->build()));
    }

    /**
     * @test
     */
    function a_single_or_where_returns_a_single_constraint()
    {
        $constraint = Builder::make()->orWhere('active', false)->build();

        $this->assertTrue($constraint->equals(Constraint::where('active', false)));
    }

    /**
     * @test
     */
    function two_wheres_return_an_and_group()
    {
        $builder = Builder::make()
            ->where('active', false)
            ->where('age', '>=', 18);

        $expected = Group::ofType('and')
            ->add(Constraint::where('active', false))
            ->add(Constraint::where('age', '>=', 18));

        $this->assertTrue($expected->equals($builder->build()));
    }

    /**
     * @test
     */
    function two_where_columns_return_an_and_group()
    {
        $builder = Builder::make()
            ->whereColumn('active', false)
            ->whereColumn('age', '>=', 18);

        $expected = Group::ofType('and')
            ->add(Constraint::whereColumn('active', false))
            ->add(Constraint::whereColumn('age', '>=', 18));

        $this->assertTrue($expected->equals($builder->build()));
    }

    /**
     * @test
     */
    function or_wheres_return_an_or_group()
    {
        $builder = Builder::make()
            ->where('active', false)
            ->orWhere('age', '>=', 18);

        $expected = Group::ofType('or')
            ->add(Constraint::where('active', false))
            ->add(Constraint::where('age', '>=', 18));

        $this->assertTrue($expected->equals($builder->build()));
    }
}
