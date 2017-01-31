<?php

use Illuminate\Support\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Silber\Bouncer\Database\Models;

class UpgradeToBouncer1Dot0 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->createNewTables();
        $this->addNewColumns();
        $this->migrateData();
        $this->deleteOldTables();
        $this->updateIndex();
    }

    /**
     * Create the new tables in Bouncer 1.0.
     *
     * @return void
     */
    protected function createNewTables()
    {
        Schema::create('assigned_roles', function (Blueprint $table) {
            $table->integer('role_id')->unsigned()->index();
            $table->morphs('entity');

            $table->foreign('role_id')->references('id')->on('roles')
                  ->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->integer('ability_id')->unsigned()->index();
            $table->morphs('entity');
            $table->boolean('forbidden')->default(false);

            $table->foreign('ability_id')->references('id')->on('abilities')
                  ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Add new columns in Bouncer 1.0.
     *
     * @return void
     */
    protected function addNewColumns()
    {
        Schema::table('abilities', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name');
            $table->boolean('only_owned')->after('entity_type')->default(false);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name');
            $table->integer('level')->unsigned()->nullable()->after('name');
        });
    }

    /**
     * Migrate the data from the old tables to the new ones.
     *
     * @return void
     */
    protected function migrateData()
    {
        $this->migrateUserRoles();
        $this->migrateUserAbilities();
        $this->migrateRoleAbilities();
    }

    /**
     * Migrate the data from the user_roles table.
     *
     * @return void
     */
    protected function migrateUserRoles()
    {
        $pivots = DB::table('user_roles')->get();

        $type = Models::user()->getMorphClass();

        $records = array_map(function ($pivot) use ($type) {
            return [
                'role_id'     => $pivot->role_id,
                'entity_type' => $type,
                'entity_id'   => $pivot->user_id,
            ];
        }, $this->toArray($pivots));

        DB::table('assigned_roles')->insert($records);
    }

    /**
     * Migrate the data from the user_abilities table.
     *
     * @return void
     */
    protected function migrateUserAbilities()
    {
        $pivots = DB::table('user_abilities')->get();

        $type = Models::user()->getMorphClass();

        $records = array_map(function ($pivot) use ($type) {
            return [
                'ability_id'  => $pivot->ability_id,
                'entity_type' => $type,
                'entity_id'   => $pivot->user_id,
            ];
        }, $this->toArray($pivots));

        DB::table('permissions')->insert($records);
    }

    /**
     * Migrate the data from the role_abilities table.
     *
     * @return void
     */
    protected function migrateRoleAbilities()
    {
        $pivots = DB::table('role_abilities')->get();

        $type = Models::role()->getMorphClass();

        $records = array_map(function ($pivot) use ($type) {
            return [
                'ability_id'  => $pivot->ability_id,
                'entity_type' => $type,
                'entity_id'   => $pivot->role_id,
            ];
        }, $this->toArray($pivots));

        DB::table('permissions')->insert($records);
    }

    /**
     * Delete the old tables that are not in use anymore.
     *
     * @return void
     */
    protected function deleteOldTables()
    {
        Schema::drop('role_abilities');
        Schema::drop('user_abilities');
        Schema::drop('user_roles');
    }

    /**
     * Replace the old unique index on the abilities table.
     *
     * @return void
     */
    protected function updateIndex()
    {
        Schema::table('abilities', function (Blueprint $table) {
            $table->dropUnique('abilities_name_entity_id_entity_type_unique');

            $table->string('name', 150)->change();
            $table->string('entity_type', 150)->nullable()->change();

            $table->unique(
                ['name', 'entity_id', 'entity_type', 'only_owned'],
                'abilities_unique_index'
            );
        });
    }

    /**
     * Convert the given list into an array.
     *
     * @param  array|\Illuminate\Support\Collection  $list
     * @return array
     */
    protected function toArray($list)
    {
        return $list instanceof Collection ? $list->all() : $list;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration does not have a rollback. The new schema is
        // polymorphic and has additional data, so creating a down
        // migration will prove quite difficult and error-prone.
        //
        // If you anticipate the need to rollback migrations, do this:
        // after running this migration, delete both this migration
        // and the original. Then re-publish the main migration.
        //
        // The final step of the process is to tweak the main migrations
        // table. Delete the records for the two migrations that have
        // already run, then add a record for the republished one.
    }
}
