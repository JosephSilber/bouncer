<?php

namespace Silber\Bouncer\Database;

use Illuminate\Database\Eloquent\Model;

class Ability extends Model
{
    use Concerns\IsAbility;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'title'];

    /**
     * Constructor.
     *
     * @param array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Models::table('abilities');

        parent::__construct($attributes);
    }
}
