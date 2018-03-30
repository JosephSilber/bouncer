<?php

namespace Silber\Bouncer\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Silber\Bouncer\Database\Models;

class CleanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bouncer:clean
                            {--u|unassigned : Whether to delete abilities not assigned to anyone}
                            {--o|orphaned : Whether to delete abilities for missing models}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete abilities that are no longer in use';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        list($unassigned, $orphaned) = $this->getComputedOptions();

        if ($unassigned) {
            $this->deleteUnassignedAbilities();
        }

        if ($orphaned) {
            $this->deleteOrphanedAbilities();
        }
    }

    /**
     * Get the options to use, computed by omission.
     *
     * @return array
     */
    protected function getComputedOptions()
    {
        $unassigned = $this->option('unassigned');
        $orphaned = $this->option('orphaned');

        if (! $unassigned && ! $orphaned) {
            $unassigned = $orphaned = true;
        }

        return [$unassigned, $orphaned];
    }

    /**
     * Delete abilities not assigned to anyone.
     *
     * @return void
     */
    protected function deleteUnassignedAbilities()
    {
        $query = $this->getUnassignedAbilitiesQuery();

        if (($count = $query->count()) > 0) {
            $query->delete();

            $this->info("Deleted {$count} unassigned ".Str::plural('ability', $count).'.');
        } else {
            $this->info('No unassigned abilities.');
        }
    }

    /**
     * Get the base query for all unassigned abilities.
     *
     * @return \Illuminate\Database\Eloquent\Query
     */
    protected function getUnassignedAbilitiesQuery()
    {
        $model = Models::ability();

        return $model->whereNotIn($model->getKeyName(), function ($query) {
            $query->from(Models::table('permissions'))->select('ability_id');
        });
    }

    /**
     * Delete model abilities whose models have been deleted.
     *
     * @return void
     */
    protected function deleteOrphanedAbilities()
    {
        $query = $this->getBaseOrphanedQuery()->where(function ($query) {
            foreach ($this->getEntityModels() as $modelName) {
                $query->orWhere(function ($query) use ($modelName) {
                    $this->scopeQueryToWhereModelIsMissing($query, $modelName);
                });
            }
        });

        if (($count = $query->count()) > 0) {
            $query->delete();

            $this->info("Deleted {$count} orphaned ".Str::plural('ability', $count).'.');
        } else {
            $this->info('No orphaned abilities.');
        }
    }

    /**
     * Scope the given query to where the ability's model is missing.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $modelName
     * @return void
     */
    protected function scopeQueryToWhereModelIsMissing($query, $modelName)
    {
        $model = new $modelName;
        $table = $this->abilitiesTable();

        $query->where("{$table}.entity_type", $modelName);

        $query->whereNotIn("{$table}.entity_id", function ($query) use ($modelName) {
            $model = new $modelName;
            $table = $model->getTable();

            $query->from($table)->select($table.'.'.$model->getKeyName());
        });
    }

    /**
     * Get the model names of all model abilities.
     *
     * @return iterable
     */
    protected function getEntityModels()
    {
        return $this->getBaseOrphanedQuery()->distinct()
                     ->get(['entity_type'])->pluck('entity_type');
    }

    /**
     * Get the base query for abilities with missing models.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getBaseOrphanedQuery()
    {
        $table = $this->abilitiesTable();

        return Models::ability()
                     ->whereNotNull("{$table}.entity_id")
                     ->where("{$table}.entity_id", '!=', '*')
                     ->where("{$table}.entity_type", '!=', '*');
    }

    /**
     * Get the name of the abilities table.
     *
     * @return string
     */
    protected function abilitiesTable()
    {
        return Models::ability()->getTable();
    }
}
