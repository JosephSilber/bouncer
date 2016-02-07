<?php

namespace Silber\Bouncer;

use Silber\Bouncer\Database\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Helper
{
    /**
     * Map a list of authorities by their class name.
     *
     * @param  array  $authorities
     * @return array
     */
    public static function mapAuthorityByClass(array $authorities)
    {
        $map = [];

        foreach ($authorities as $authority) {
            if ($authority instanceof Model) {
                $map[get_class($authority)][] = $authority->getKey();
            } else {
                $map[Models::classname(User::class)][] = $authority;
            }
        }

        return $map;
    }
}
