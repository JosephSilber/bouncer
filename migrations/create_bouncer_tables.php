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
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('abilities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->unique();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->unique();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->integer('role_id')->unsigned();
            $table->integer('user_id')->unsigned();
        });

        Schema::create('user_abilities', function (Blueprint $table) {
            $table->integer('ability_id')->unsigned();
            $table->integer('user_id')->unsigned();
        });

        Schema::create('role_abilities', function (Blueprint $table) {
            $table->integer('ability_id')->unsigned();
            $table->integer('role_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
        Schema::drop('abilities');
        Schema::drop('roles');
        Schema::drop('user_roles');
        Schema::drop('user_abilities');
        Schema::drop('role_abilities');
    }
}
