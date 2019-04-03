<?php

namespace MikesLumenApi\Middlewares;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use MikesLumenBase\Utils\AuthHelper;
use Dingo\Api\Contract\Http\Request;

class Authority
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->method() !== 'OPTIONS') {
            $authHelper = new AuthHelper($request);
            $role = $authHelper->getRequestRole();
            $route = $authHelper->getRoute();
            $method = $authHelper->getRequestMethodLowerCase();

            if (!$authHelper->isAuthorized($role, $route, $method)) {
                throw new AuthorizationException($authHelper->getUnauthorizedMessage());
            }
        }

        return $next($request);
    }
}
