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
            $table->string('name', 150);
            $table->string('title')->nullable();
            $table->integer('entity_id')->unsigned()->nullable();
            $table->string('entity_type', 150)->nullable();
            $table->boolean('only_owned')->default(false);
            $table->timestamps();

            $table->unique(
                ['name', 'entity_id', 'entity_type', 'only_owned'],
                'abilities_unique_index'
            );
        });

        Schema::create(Models::table('roles'), function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('title')->nullable();
            $table->integer('level')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::create(Models::table('assigned_roles'), function (Blueprint $table) {
            $table->integer('role_id')->unsigned()->index();
            $table->morphs('entity');

            $table->foreign('role_id')->references('id')->on(Models::table('roles'))
                  ->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create(Models::table('permissions'), function (Blueprint $table) {
            $table->integer('ability_id')->unsigned()->index();
            $table->morphs('entity');
            $table->boolean('forbidden')->default(false);

            $table->foreign('ability_id')->references('id')->on(Models::table('abilities'))
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
        Schema::drop(Models::table('permissions'));
        Schema::drop(Models::table('assigned_roles'));
        Schema::drop(Models::table('roles'));
        Schema::drop(Models::table('abilities'));
    }
}
