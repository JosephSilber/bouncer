<?php

class MultipleAbilitiesTest extends BaseTestCase
{

    /**
     * When set to true, queries are counted and reported for tests in this file.
     * @var boolean
     */
    public $report = false;

    public function setUp()
    {
        parent::setUp();

        if($this->report) {
            $this->db()->connection()->enableQueryLog();
        }
    }

    public function tearDown()
    {
        if($this->report) {
            var_dump($this->getName().': '.count($this->db()->connection()->getQueryLog()));
        }

        parent::tearDown();
    }

    public function test_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));

        $bouncer->disallow($user)->to('edit');

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->allows('delete'));
    }

    public function test_multiple_abilties_with_model_reference()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->allows('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', User::class));

        $bouncer->disallow($user)->to('edit', User::class);

        $this->assertTrue($bouncer->denies('edit', User::class));
        $this->assertTrue($bouncer->allows('delete', User::class));
    }

    public function test_allow_multiple_abilties()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));
    }

    public function test_disallow_multiple_abilties()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->disallow($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('delete'));
    }

    public function test_forbid_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);
        $bouncer->forbid($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->denies('edit'));
        $this->assertTrue($bouncer->denies('delete'));
    }

    public function test_unforbid_multiple_abilities()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete']);
        $bouncer->forbid($user)->to(['edit', 'delete']);
        
        $this->assertTrue($bouncer->denies('edit'));

        $bouncer->unforbid($user)->to(['edit', 'delete']);

        $this->assertTrue($bouncer->allows('edit'));
        $this->assertTrue($bouncer->allows('delete'));
    }

    public function test_forbid_multiple_abilities_with_model_reference()
    {
        $bouncer = $this->bouncer($user = User::create())->dontCache();

        $bouncer->allow($user)->to(['edit', 'delete'], User::class);
        $bouncer->forbid($user)->to(['edit', 'delete'], User::class);

        $this->assertTrue($bouncer->denies('delete', User::class));

        $bouncer->unforbid($user)->to('delete', User::class);

        $this->assertTrue($bouncer->allows('delete', User::class));
        $this->assertFalse($bouncer->allows('edit', User::class));
    }
}