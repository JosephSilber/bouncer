<?php

class AbilitiesForModelsTest extends BaseTestCase
{
    public function test_model_blanket_ability()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1);

        $bouncer->allow($user1)->to('edit', User::class);

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->allows('edit', $user2));

        $bouncer->disallow($user1)->to('edit', User::class);
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->denies('edit', $user2));

        $bouncer->disallow($user1)->to('edit');
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->denies('edit', $user2));
    }

    public function test_individual_model_ability()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1);

        $bouncer->allow($user1)->to('edit', $user2);

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->denies('edit', $user1));
        $this->assertTrue($bouncer->allows('edit', $user2));

        $bouncer->disallow($user1)->to('edit', User::class);
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->allows('edit', $user2));

        $bouncer->disallow($user1)->to('edit', $user1);
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->allows('edit', $user2));

        $bouncer->disallow($user1)->to('edit', $user2);
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->denies('edit', $user1));
        $this->assertTrue($bouncer->denies('edit', $user2));
    }

    public function test_blanket_ability_and_individual_model_ability_are_kept_separate()
    {
        $user1 = User::create();
        $user2 = User::create();

        $bouncer = $this->bouncer($user1);

        $bouncer->allow($user1)->to('edit', User::class);
        $bouncer->allow($user1)->to('edit', $user2);

        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->allows('edit', $user2));

        $bouncer->disallow($user1)->to('edit', User::class);
        $this->clipboard->refresh();

        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->allows('edit', $user2));
    }

    public function test_allowing_on_non_existent_model_throws()
    {
        $this->setExpectedException('InvalidArgumentException');

        $user1 = User::create();
        $user2 = new User;

        $bouncer = $this->bouncer($user1);

        $bouncer->allow($user1)->to('delete', $user2);
    }
}
