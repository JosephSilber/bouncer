<?php

namespace Silber\Bouncer\Database;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use IsRole;

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
        $this->table = Models::table('roles');

        parent::__construct($attributes);
    }
}
