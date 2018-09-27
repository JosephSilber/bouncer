<?php

namespace Silber\Bouncer\Tests;

use Silber\Bouncer\Database\Role;
use Silber\Bouncer\Database\Ability;

class RoleLevelsTest extends BaseTestCase
{
    use Concerns\TestsClipboards;

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function a_role_is_allowed_abilities_from_a_lower_level($provider)
    {
        $bouncer = $this->prepareLevelsTest($provider, 2, 1);

        $this->assertTrue($bouncer->can('edit-site'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function a_role_is_not_allowed_abilities_from_the_same_level($provider)
    {
        $bouncer = $this->prepareLevelsTest($provider, 2, 2);

        $this->assertFalse($bouncer->can('edit-site'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function a_role_is_not_allowed_abilities_from_another_role_with_no_level($provider)
    {
        $bouncer = $this->prepareLevelsTest($provider, 2, null);

        $this->assertFalse($bouncer->can('edit-site'));
    }

    /**
     * @test
     * @dataProvider bouncerProvider
     */
    function a_role_with_no_level_is_not_allowed_abilities_from_another_level($provider)
    {
        $bouncer = $this->prepareLevelsTest($provider, null, 1);

        $this->assertFalse($bouncer->can('edit-site'));
    }

    protected function prepareLevelsTest($provider, $grantedLevel, $otherLevel)
    {
        list($bouncer, $user) = $provider();

        $admin = Role::create(['name' => 'admin', 'level' => $grantedLevel]);
        $editor = Role::create(['name' => 'editor', 'level' => $otherLevel]);

        $bouncer->allow($editor)->to('edit-site');
        $bouncer->assign($admin)->to($user);

        return $bouncer;
    }
}
