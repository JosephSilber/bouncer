<?php

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Models;
use Silber\Bouncer\Database\Ability;

use App\User;
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
        Schema::create($this->table(Ability::class), function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('entity_id')->unsigned()->nullable();
            $table->string('entity_type')->nullable();
            $table->timestamps();

            $table->unique(['name', 'entity_id', 'entity_type']);
        });

        Schema::create($this->table(Role::class), function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->integer('role_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->unique(['role_id', 'user_id']);

            $table->foreign('role_id')->references('id')->on($this->table(Role::class));
            $table->foreign('user_id')->references('id')->on($this->table(User::class));
        });

        Schema::create('user_abilities', function (Blueprint $table) {
            $table->integer('ability_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->unique(['ability_id', 'user_id']);

            $table->foreign('ability_id')->references('id')->on($this->table(Ability::class));
            $table->foreign('user_id')->references('id')->on($this->table(User::class));
        });

        Schema::create('role_abilities', function (Blueprint $table) {
            $table->integer('ability_id')->unsigned();
            $table->integer('role_id')->unsigned();

            $table->unique(['ability_id', 'role_id']);

            $table->foreign('ability_id')->references('id')->on($this->table(Ability::class));
            $table->foreign('role_id')->references('id')->on($this->table(Role::class));
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
        Schema::drop($this->table(Role::class));
        Schema::drop($this->table(Ability::class));
    }

    /**
     * Get the table for the given type.
     *
     * @param  string  $type
     * @return string
     */
    protected function table($type)
    {
        $class = Models::classname($type);

        return (new $class)->getTable();
    }
}
