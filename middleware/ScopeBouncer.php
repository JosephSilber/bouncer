<?php

namespace App\Http\Middleware;

use Bouncer, Closure;

class ScopeBouncer
{
    /**
     * Set the proper Bouncer scope for the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Here you may use whatever mechanism you use in your app
        // to determine the current tenant. To demonstrate, the
        // $tenantId is set here from the user's account_id.
        $tenantId = $request->user()->account_id;

        Bouncer::scope()->to($tenantId);

        return $next($request);
    }
}
