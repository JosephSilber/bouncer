<?php

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class AutoTitlesTest extends BaseTestCase
{
    public function test_role_title_is_never_overwritten()
    {
        $role = Role::create(['name' => 'admin', 'title' => 'Something Else']);

        $this->assertEquals('Something Else', $role->title);
    }

    public function test_role_title_is_capitalized()
    {
        $role = Role::create(['name' => 'admin']);

        $this->assertEquals('Admin', $role->title);
    }

    public function test_role_title_with_spaces()
    {
        $role = Role::create(['name' => 'site admin']);

        $this->assertEquals('Site admin', $role->title);
    }

    public function test_role_title_with_dashes()
    {
        $role = Role::create(['name' => 'site-admin']);

        $this->assertEquals('Site admin', $role->title);
    }

    public function test_role_title_with_underscores()
    {
        $role = Role::create(['name' => 'site_admin']);

        $this->assertEquals('Site admin', $role->title);
    }

    public function test_role_title_with_camel_casing()
    {
        $role = Role::create(['name' => 'siteAdmin']);

        $this->assertEquals('Site admin', $role->title);
    }

    public function test_role_title_with_studly_casing()
    {
        $role = Role::create(['name' => 'SiteAdmin']);

        $this->assertEquals('Site admin', $role->title);
    }
}
