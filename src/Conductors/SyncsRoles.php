<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;

class SyncsRoles
{
    /**
     * The authority for whom to sync roles.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $authority;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model  $authority
     */
    public function __construct(Model $authority)
    {
        $this->authority = $authority;
    }

    /**
     * Sync the provided roles to the authority.
     *
     * @param  iterable  $roles
     * @return void
     */
    public function roles($roles)
    {
        $this->authority->roles()->sync(Models::role()->getRoleKeys($roles), true);
    }
}
