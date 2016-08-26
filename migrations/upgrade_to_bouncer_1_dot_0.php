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
        $this->migrateData();
        $this->deleteOldTables();
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
        // If your app is still in development and doesn't have any real
        // data yet, you should now drop all tables and migrate again.
        // This'll ensure that the main migration isn't duplicated.
        //
        // If you are already in production, you will have to tweak the
        // migrations table. Delete the records for the 2 migrations
        // already run, then add a record for the republished one.
    }
}
