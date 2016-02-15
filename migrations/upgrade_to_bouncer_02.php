<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Silber\Bouncer\Database\Models;

class UpgradeToBouncer02 extends Migration
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
     * Create the new tables in Bouncer 0.2.
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
        }, $pivots);

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
        }, $pivots);

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
        }, $pivots);

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
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
