<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBouncerTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $user = app('config')->get('auth.model');

        $usersTable = (new $user)->getTable();

        Schema::create('abilities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('entity_id')->unsigned()->nullable();
            $table->string('entity_type')->nullable();
            $table->timestamps();

            $table->unique(['name', 'entity_id', 'entity_type']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) use ($usersTable) {
            $table->integer('role_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->unique(['role_id', 'user_id']);

            $table->foreign('role_id')->references('id')->on('roles');
            $table->foreign('user_id')->references('id')->on($usersTable);
        });

        Schema::create('user_abilities', function (Blueprint $table) use ($usersTable) {
            $table->integer('ability_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->unique(['ability_id', 'user_id']);

            $table->foreign('ability_id')->references('id')->on('abilities');
            $table->foreign('user_id')->references('id')->on($usersTable);
        });

        Schema::create('role_abilities', function (Blueprint $table) {
            $table->integer('ability_id')->unsigned();
            $table->integer('role_id')->unsigned();

            $table->unique(['ability_id', 'role_id']);

            $table->foreign('ability_id')->references('id')->on('abilities');
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('role_abilities');
        Schema::drop('user_abilities');
        Schema::drop('user_roles');
        Schema::drop('roles');
        Schema::drop('abilities');
    }
}
