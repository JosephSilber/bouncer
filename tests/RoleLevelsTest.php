<?php

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class RoleLevelsTest extends BaseTestCase
{
    public function test_a_role_is_allowed_abilities_from_a_lower_level()
    {
        $bouncer = $this->prepareLevelsTest(2, 1);

        $this->assertTrue($bouncer->can('edit-site'));
    }

    public function test_a_role_is_not_allowed_abilities_from_the_same_level()
    {
        $bouncer = $this->prepareLevelsTest(2, 2);

        $this->assertFalse($bouncer->can('edit-site'));
    }

    public function test_a_role_is_not_allowed_abilities_from_another_role_with_no_level()
    {
        $bouncer = $this->prepareLevelsTest(2, null);

        $this->assertFalse($bouncer->can('edit-site'));
    }

    public function test_a_role_with_no_level_is_not_allowed_abilities_from_another_level()
    {
        $bouncer = $this->prepareLevelsTest(null, 1);

        $this->assertFalse($bouncer->can('edit-site'));
    }

    protected function prepareLevelsTest($grantedLevel, $otherLevel)
    {
        $bouncer = $this->bouncer($user = User::create());

        $admin = Role::create(['name' => 'admin', 'level' => $grantedLevel]);
        $editor = Role::create(['name' => 'editor', 'level' => $otherLevel]);

        $bouncer->allow($editor)->to('edit-site');
        $bouncer->assign($admin)->to($user);

        return $bouncer;
    }
}
