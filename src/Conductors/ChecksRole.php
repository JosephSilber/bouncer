<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Clipboard;
use Silber\Bouncer\Database\Role;
use Illuminate\Database\Eloquent\Model;

class ChecksRole
{
    /**
     * The user against whom to check for roles.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $user;

    /**
     * The bouncer clipboard instance.
     *
     * @var \Silber\Bouncer\Clipboard
     */
    protected $clipboard;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model  $user
     * @param \Silber\Bouncer\Clipboard  $clipboard
     */
    public function __construct(Model $user, Clipboard $clipboard)
    {
        $this->user = $user;
        $this->clipboard = $clipboard;
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param  string  $role
     * @return bool
     */
    public function a($role)
    {
        $roles = func_get_args();

        return $this->clipboard->checkRole($this->user, $roles, 'or');
    }

    /**
     * Alias to the "a" method.
     *
     * @param  string  $role
     * @return bool
     */
    public function an($role)
    {
        $roles = func_get_args();

        return $this->clipboard->checkRole($this->user, $roles, 'or');
    }

    /**
     * Check if the user has all of the given roles.
     *
     * @param  string  $role
     * @return bool
     */
    public function all($role)
    {
        $roles = func_get_args();

        return $this->clipboard->checkRole($this->user, $roles, 'and');
    }
}
