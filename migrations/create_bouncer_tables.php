<?php

use Silber\Bouncer\Database\Models;

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
        Schema::create(Models::table('abilities'), function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('entity_id')->unsigned()->nullable();
            $table->string('entity_type')->nullable();
            $table->timestamps();

            $table->unique(['name', 'entity_id', 'entity_type']);
        });

        Schema::create(Models::table('roles'), function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create(Models::table('user_roles'), function (Blueprint $table) {
            $table->integer('role_id')->unsigned()->index();
            $table->integer('user_id')->unsigned()->index();

            $table->primary(['role_id', 'user_id']);

            $table->foreign('role_id')->references('id')->on(Models::table('roles'))
                  ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('user_id')->references('id')->on(Models::table('users'))
                  ->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create(Models::table('user_abilities'), function (Blueprint $table) {
            $table->integer('ability_id')->unsigned()->index();
            $table->integer('user_id')->unsigned()->index();

            $table->primary(['ability_id', 'user_id']);

            $table->foreign('ability_id')->references('id')->on(Models::table('abilities'))
                  ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('user_id')->references('id')->on(Models::table('users'))
                  ->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create(Models::table('role_abilities'), function (Blueprint $table) {
            $table->integer('ability_id')->unsigned()->index();
            $table->integer('role_id')->unsigned()->index();

            $table->primary(['ability_id', 'role_id']);

            $table->foreign('ability_id')->references('id')->on(Models::table('abilities'))
                  ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('role_id')->references('id')->on(Models::table('roles'))
                  ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Models::table('role_abilities'));
        Schema::drop(Models::table('user_abilities'));
        Schema::drop(Models::table('user_roles'));
        Schema::drop(Models::table('roles'));
        Schema::drop(Models::table('abilities'));
    }
}
