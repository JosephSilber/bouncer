<?php

namespace Silber\Bouncer\Middleware;

use Closure;
use Illuminate\Auth\Access\Gate;
use Illuminate\Auth\Access\HandlesAuthorization;

class Authorize
{
    use HandlesAuthorization;

    /**
     * The access gate instance.
     *
     * @var \Illuminate\Auth\Access\Gate
     */
    protected $gate;

    /**
     * Constructor.
     *
     * @param \Illuminate\Auth\Access\Gate  $gate
     */
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $ability
     * @param  string|null  $model
     * @return mixed
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle($request, Closure $next, $ability = null, $model = null)
    {
        if ( ! $request->user()) {
            return $this->unauthorized($request);
        }

        $this->gate->authorize($ability, $model ? $request->route($model) : []);

        return $next($request);
    }

    /**
     * Create an unauthorized response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthorized($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response('Unauthorized.', 401);
        } else {
            return redirect()->guest('login');
        }
    }
}
