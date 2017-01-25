<?php

namespace Silber\Bouncer\Database;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use Concerns\IsRole;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'title', 'level'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['level' => 'int'];

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
