<?php

use Illuminate\Routing\Controller;
use Silber\Bouncer\Middleware\AuthorizesResources;

class AuthorizesResourcesTest extends PHPUnit_Framework_TestCase
{
    public function test_authorizes_resources()
    {
        $map = [
            'index'  => 'can:view,App\User',
            'create' => 'can:create,App\User',
            'store'  => 'can:create,App\User',
            'show'   => 'can:view,user',
            'edit'   => 'can:update,user',
            'update' => 'can:update,user',
            'delete' => 'can:delete,user',
        ];

        foreach ($map as $method => $middleware) {
            $controller = new AuthorizesResourcesController(new RequestStub($method));

            $this->assertEquals(array_keys($controller->getMiddleware()), [$middleware]);
        }
    }
}

class RequestStub
{
    protected $method;

    public function __construct($method)
    {
        $this->method = $method;
    }

    public function route()
    {
        return new RouteStub($this->method);
    }
}

class RouteStub
{
    protected $method;

    public function __construct($method)
    {
        $this->method = $method;
    }

    public function getActionName()
    {
        return 'AuthorizesResourcesController@'.$this->method;
    }
}

class AuthorizesResourcesController extends Controller
{
    use AuthorizesResources;

    public function __construct($request)
    {
        $this->authorizeResource('user', 'App\User', [], $request);
    }
}
