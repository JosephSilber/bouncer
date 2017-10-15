<?php

use Silber\Bouncer\Database\HasRolesAndAbilities;

class CustomAuthorityTest extends BaseTestCase
{
    protected function migratedTestTables()
    {
        Schema::create('accounts', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    protected function rollbackTestTables()
    {
        Schema::drop('accounts');
    }

    public function test_bouncer_can_give_and_remove_abilities()
    {
        $bouncer = $this->bouncer($account = Account::create())->dontCache();

        $bouncer->allow($account)->to('edit-site');

        $this->assertTrue($bouncer->can('edit-site'));

        $bouncer->disallow($account)->to('edit-site');

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    public function test_bouncer_can_give_and_remove_roles()
    {
        $bouncer = $this->bouncer($account = Account::create())->dontCache();

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($account);

        $editor = $bouncer->role()->create(['name' => 'editor']);
        $bouncer->allow($editor)->to('edit-site');
        $bouncer->assign($editor)->to($account);

        $this->assertTrue($bouncer->can('edit-site'));

        $bouncer->retract('admin')->from($account);
        $bouncer->retract($editor)->from($account);

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    public function test_bouncer_can_disallow_abilities_on_roles()
    {
        $bouncer = $this->bouncer($account = Account::create());

        $bouncer->allow('admin')->to('edit-site');
        $bouncer->disallow('admin')->to('edit-site');
        $bouncer->assign('admin')->to($account);

        $this->assertTrue($bouncer->cannot('edit-site'));
    }

    public function test_bouncer_can_check_user_roles()
    {
        $bouncer = $this->bouncer($account = Account::create());

        $this->assertTrue($bouncer->is($account)->notA('moderator'));
        $this->assertTrue($bouncer->is($account)->notAn('editor'));
        $this->assertFalse($bouncer->is($account)->an('admin'));

        $bouncer = $this->bouncer($account = Account::create());

        $bouncer->assign('moderator')->to($account);
        $bouncer->assign('editor')->to($account);

        $this->assertTrue($bouncer->is($account)->a('moderator'));
        $this->assertTrue($bouncer->is($account)->an('editor'));
        $this->assertFalse($bouncer->is($account)->notAn('editor'));
        $this->assertFalse($bouncer->is($account)->an('admin'));
    }

    public function test_bouncer_can_check_multiple_user_roles()
    {
        $bouncer = $this->bouncer($account = Account::create());

        $this->assertTrue($bouncer->is($account)->notAn('editor', 'moderator'));
        $this->assertTrue($bouncer->is($account)->notAn('admin', 'moderator'));

        $bouncer = $this->bouncer($account = Account::create());
        $bouncer->assign('moderator')->to($account);
        $bouncer->assign('editor')->to($account);

        $this->assertTrue($bouncer->is($account)->a('subscriber', 'moderator'));
        $this->assertTrue($bouncer->is($account)->an('admin', 'editor'));
        $this->assertTrue($bouncer->is($account)->all('editor', 'moderator'));
        $this->assertFalse($bouncer->is($account)->notAn('editor', 'moderator'));
        $this->assertFalse($bouncer->is($account)->all('admin', 'moderator'));
    }
}
