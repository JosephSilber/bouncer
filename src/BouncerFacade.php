<?php

namespace Silber\Bouncer;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Silber\Bouncer\Conductors\GivesAbilities allow(\Illuminate\Database\Eloquent\Model|string $authority)
 * @method static \Silber\Bouncer\Conductors\GivesAbilities allowEveryone()
 * @method static \Silber\Bouncer\Conductors\RemovesAbilities disallow(\Illuminate\Database\Eloquent\Model|string $authority)
 * @method static \Silber\Bouncer\Conductors\RemovesAbilities disallowEveryone()
 * @method static \Silber\Bouncer\Conductors\GivesAbilities forbid(\Illuminate\Database\Eloquent\Model|string $authority)
 * @method static \Silber\Bouncer\Conductors\GivesAbilities forbidEveryone()
 * @method static \Silber\Bouncer\Conductors\RemovesAbilities unforbid(\Illuminate\Database\Eloquent\Model|string $authority)
 * @method static \Silber\Bouncer\Conductors\RemovesAbilities unforbidEveryone()
 * @method static \Silber\Bouncer\Conductors\AssignsRoles assign(\Silber\Bouncer\Database\Role|\Illuminate\Support\Collection|string $roles)
 * @method static \Silber\Bouncer\Conductors\RemovesRoles retract(\Illuminate\Support\Collection|\Silber\Bouncer\Database\Role|string $roles)
 * @method static \Silber\Bouncer\Conductors\SyncsRolesAndAbilities sync(\Illuminate\Database\Eloquent\Model|string $authority)
 * @method static \Silber\Bouncer\Conductors\ChecksRoles is(\Illuminate\Database\Eloquent\Model $authority)
 * @method static \Silber\Bouncer\Contracts\Clipboard getClipboard()
 * @method static self setClipboard(\Silber\Bouncer\Contracts\Clipboard $clipboard)
 * @method static self registerClipboardAtContainer()
 * @method static self cache(null|\Illuminate\Contracts\Cache\Store $cache)
 * @method static self dontCache()
 * @method static self refresh(null|\Illuminate\Database\Eloquent\Model $authority)
 * @method static self refreshFor(\Illuminate\Database\Eloquent\Model $authority)
 * @method static self setGate(\Illuminate\Contracts\Auth\Access\Gate $gate)
 * @method static \Illuminate\Contracts\Auth\Access\Gate|null getGate()
 * @method static \Illuminate\Contracts\Auth\Access\Gate gate()
 * @method static bool usesCachedClipboard()
 * @method static self define(string $ability, callable|string $callback)
 * @method static \Illuminate\Auth\Access\Response authorize(string $ability, array|mixed $arguments)
 * @method static bool can(string $ability, array|mixed $arguments)
 * @method static bool canAny(array $abilities, array|mixed $arguments)
 * @method static bool cannot(string $ability, array|mixed $arguments)
 * @method static bool allows(string $ability, array|mixed $arguments)
 * @method static bool denies(string $ability, array|mixed $arguments)
 * @method static \Silber\Bouncer\Database\Role role(array $attributes)
 * @method static \Silber\Bouncer\Database\Ability ability(array $attributes)
 * @method static self runBeforePolicies(bool $boolean)
 * @method static self ownedVia(string|\Closure $model, string|\Closure|null $attribute)
 * @method static self useAbilityModel(string $model)
 * @method static self useRoleModel(string $model)
 * @method static self useUserModel(string $model)
 * @method static self tables(array $map)
 * @method static mixed scope(null|\Silber\Bouncer\Contracts\Scope $scope)
 * 
 * @see \Silber\Bouncer\Bouncer
 */
class BouncerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Bouncer::class;
    }
}
