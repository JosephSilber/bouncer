<?php

namespace Silber\Bouncer\Conductors;

use Silber\Bouncer\Helpers;
use Silber\Bouncer\Database\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SyncsRolesAndAbilities
{
    use Concerns\FindsAndCreatesAbilities;

    /**
     * The authority for whom to sync roles/abilities.
     *
     * @var \Illuminate\Database\Eloquent\Model|string
     */
    protected $authority;

    /**
     * Constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model|string  $authority
     */
    public function __construct($authority)
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
        $this->sync(
            Models::role()->getRoleKeys($roles),
            $this->authority->roles()
        );
    }

    /**
     * Sync the provided abilities to the authority.
     *
     * @param  iterable  $abilities
     * @return void
     */
    public function abilities($abilities)
    {
        $this->syncAbilities($abilities);
    }

    /**
     * Sync the provided forbidden abilities to the authority.
     *
     * @param  iterable  $abilities
     * @return void
     */
    public function forbiddenAbilities($abilities)
    {
        $this->syncAbilities($abilities, ['forbidden' => true]);
    }

    /**
     * Sync the given abilities for the authority.
     *
     * @param  iterable  $abilities
     * @param  array  $options
     * @return void
     */
    protected function syncAbilities($abilities, $options = ['forbidden' => false])
    {
        $abilityKeys = $this->getAbilityIds($abilities);
        $authority = $this->getAuthority();
        $relation = $authority->abilities();

        $this->newPivotQuery($relation)
             ->where('entity_type', $authority->getMorphClass())
             ->whereNotIn($this->getRelatedPivotKeyName($relation), $abilityKeys)
             ->where('forbidden', $options['forbidden'])
             ->delete();

        if ($options['forbidden']) {
            (new ForbidsAbilities($this->authority))->to($abilityKeys);
        } else {
            (new GivesAbilities($this->authority))->to($abilityKeys);
        }
    }

    /**
     * Get the authority for whom to sync roles/abilities.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getAuthority()
    {
        if ($this->authority instanceof Model) {
            return $this->authority;
        }

        return Models::role()->firstOrCreate(['name' => $this->authority]);
    }

    /**
     * Get the fully qualified column name for the abilities table's primary key.
     *
     * @return string
     */
    protected function getAbilitiesQualifiedKeyName()
    {
        $model = Models::ability();

        return $model->getTable().'.'.$model->getKeyName();
    }

    /**
     * Sync the given IDs on the pivot relation.
     *
     * This is a heavily-modified version of Eloquent's built-in
     * BelongsToMany@sync - which we sadly cannot use because
     * our scope sets a "closure where" on the pivot table.
     *
     * @param  array  $ids
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relation
     * @return void
     */
    protected function sync(array $ids, BelongsToMany $relation)
    {
        $current = $this->pluck(
            $this->newPivotQuery($relation),
            $this->getRelatedPivotKeyName($relation)
        );

        $this->detach(array_diff($current, $ids), $relation);

        $relation->attach(
            array_diff($ids, $current),
            Models::scope()->getAttachAttributes($this->authority)
        );
    }

    /**
     * Detach the records with the given IDs from the relationship.
     *
     * @param  array  $ids
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relation
     * @return void
     */
    public function detach(array $ids, BelongsToMany $relation)
    {
        if (empty($ids)) {
            return;
        }

        $this->newPivotQuery($relation)
             ->whereIn($this->getRelatedPivotKeyName($relation), $ids)
             ->delete();
    }

    /**
     * Get a scoped query for the pivot table.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relation
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newPivotQuery(BelongsToMany $relation)
    {
        $query = $relation->newPivotStatement()->where(
            $this->getForeignPivotKeyName($relation),
            $relation->getParent()->getKey()
        );

        return Models::scope()->applyToRelationQuery(
            $query, $relation->getTable()
        );
    }

    /**
     * Get the column name for the foreign key on the pivot table.
     *
     * The name of the underlying method changed in
     * Laravel 5.4, and then again in 5.5, so we
     * first check which method is available.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relation
     * @return string
     */
    protected function getForeignPivotKeyName(BelongsToMany $relation)
    {
        if (method_exists($relation, 'getForeignPivotKeyName')) {
            return $relation->getForeignPivotKeyName();
        }

        if (method_exists($relation, 'getQualifiedForeignKeyName')) {
            return $relation->getQualifiedForeignKeyName();
        }

        return $relation->getForeignKey();
    }

    /**
     * Get the column name for the related key on the pivot table.
     *
     * The name of the underlying method changed in
     * Laravel 5.4, and then again in 5.5, so we
     * first check which method is available.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relation
     * @return string
     */
    protected function getRelatedPivotKeyName(BelongsToMany $relation)
    {
        if (method_exists($relation, 'getRelatedPivotKeyName')) {
            return $relation->getRelatedPivotKeyName();
        }

        if (method_exists($relation, 'getQualifiedRelatedKeyName')) {
            return $relation->getQualifiedRelatedKeyName();
        }

        return $relation->getOtherKey();
    }

    /**
     * Pluck the values of the given column using the provided query.
     *
     * @param  mixed  $query
     * @param  string $column
     * @return string[]
     */
    protected function pluck($query, $column)
    {
        return Arr::pluck($query->get([$column]), last(explode('.', $column)));
    }
}
