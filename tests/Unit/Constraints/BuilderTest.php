<?php

namespace Silber\Bouncer\Tests\Unit\Constraints;

use PHPUnit\Framework\TestCase;
use Silber\Bouncer\Constraints\Group;
use Silber\Bouncer\Constraints\Builder;
use Silber\Bouncer\Constraints\Constraint;

class BuilderTest extends TestCase
{
    /**
     * @test
     */
    function building_without_constraints_returns_empty_group()
    {
        $actual = (new Builder())->build();

        $expected = new Group;

        $this->assertTrue($expected->equals($actual));
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
    function a_single_or_where_returns_a_single_or_constraint()
    {
        $actual = Builder::make()->orWhere('active', false)->build();

        $expected = Constraint::orWhere('active', false);

        $this->assertTrue($expected->equals($actual));
    }

    /**
     * @test
     */
    function two_wheres_return_a_group()
    {
        $builder = Builder::make()
            ->where('active', false)
            ->where('age', '>=', 18);

        $expected = (new Group)
            ->add(Constraint::where('active', false))
            ->add(Constraint::where('age', '>=', 18));

        $this->assertTrue($expected->equals($builder->build()));
    }

    /**
     * @test
     */
    function two_where_columns_return_a_group()
    {
        $builder = Builder::make()
            ->whereColumn('active', false)
            ->whereColumn('age', '>=', 18);

        $expected = $expected = (new Group)
            ->add(Constraint::whereColumn('active', false))
            ->add(Constraint::whereColumn('age', '>=', 18));

        $this->assertTrue($expected->equals($builder->build()));
    }

    /**
     * @test
     */
    function or_wheres_return_a_group()
    {
        $builder = Builder::make()
            ->where('active', false)
            ->orWhere('age', '>=', 18);

        $expected = (new Group)
            ->add(Constraint::where('active', false))
            ->add(Constraint::orWhere('age', '>=', 18));

        $this->assertTrue($expected->equals($builder->build()));
    }

    /**
     * @test
     */
    function nested_wheres_return_a_group()
    {
        $builder = Builder::make()->where('active', false)->where(function ($query) {
            $query->where('a', 'b')->where('c', 'd');
        });

        $expected = (new Group)
            ->add(Constraint::where('active', false))
            ->add(
                (new Group)
                    ->add(Constraint::where('a', 'b'))
                    ->add(Constraint::where('c', 'd'))
            );

        $this->assertTrue($expected->equals($builder->build()));
    }

    /**
     * @test
     */
    function nested_or_where_returns_an_or_group()
    {
        $builder = Builder::make()->where('active', false)->orWhere(function ($query) {
            $query->where('a', 'b')->where('c', 'd');
        });

        $expected = (new Group)
            ->add(Constraint::where('active', false))
            ->add(
                Group::withOr()
                    ->add(Constraint::where('a', 'b'))
                    ->add(Constraint::where('c', 'd'))
            );

        $this->assertTrue($expected->equals($builder->build()));
    }

    /**
     * @test
     */
    function can_nest_multiple_levels()
    {
        $builder = Builder::make()
            ->where('active', false)
            ->orWhere(function ($query) {
                $query->where('a', 'b')->where('c', 'd')->where(function ($query) {
                    $query->where('1', '2')->orWhere('3', '4');
                });
            });

        $expected = (new Group)
            ->add(Constraint::where('active', false))
            ->add(
                Group::withOr()
                    ->add(Constraint::where('a', 'b'))
                    ->add(Constraint::where('c', 'd'))
                    ->add(
                        Group::withAnd()
                            ->add(Constraint::where('1', '2'))
                            ->add(Constraint::orWhere('3', '4'))
                    )
            );

        $this->assertTrue($expected->equals($builder->build()));
    }
}
