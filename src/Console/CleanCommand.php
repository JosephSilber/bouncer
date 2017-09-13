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
                            {--o|orphaned : Whether to delete orphaned abilities}
                            {--m|missing : Whether to delete abilities for missing models}';

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
        list($orphaned, $missing) = $this->getComputedOptions();

        if ($orphaned) {
            $this->deleteOrphanedAbilities();
        }

        if ($missing) {
            $this->deleteAbilitiesForMissingModels();
        }
    }

    /**
     * Get the options to use, computed by omission.
     *
     * @return array
     */
    protected function getComputedOptions()
    {
        $orphaned = $this->option('orphaned');
        $missing = $this->option('missing');

        if (! $orphaned && ! $missing) {
            $orphaned = $missing = true;
        }

        return [$orphaned, $missing];
    }

    /**
     * Delete abilities not assigned to anyone.
     *
     * @return void
     */
    protected function deleteOrphanedAbilities()
    {
        $query = $this->getOrphanedAbilitiesQuery();

        if (($count = $query->count()) > 0) {
            $query->delete();

            $this->info("Deleted {$count} orphaned ".Str::plural('ability', $count).'.');
        } else {
            $this->info('No orphaned abilities.');
        }
    }

    /**
     * Get the base query for all unassigned abilities.
     *
     * @return \Illuminate\Database\Eloquent\Query
     */
    protected function getOrphanedAbilitiesQuery()
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
    protected function deleteAbilitiesForMissingModels()
    {
        $query = $this->getBaseMissingQuery()->where(function ($query) {
            foreach ($this->getEntityModels() as $modelName) {
                $query->orWhere(function ($query) use ($modelName) {
                    $this->scopeQueryToWhereModelIsMissing($query, $modelName);
                });
            }
        });

        if (($count = $query->count()) > 0) {
            $query->delete();

            if ($count == 1) {
                $this->info('Deleted 1 ability with a missing model.');
            } else {
                $this->info("Deleted {$count} abilities with missing models.");
            }
        } else {
            $this->info('No abilities with missing models.');
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
        return $this->getBaseMissingQuery()->distinct()
                     ->get(['entity_type'])->pluck('entity_type');
    }

    /**
     * Get the base query for abilities woth missing models.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getBaseMissingQuery()
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
