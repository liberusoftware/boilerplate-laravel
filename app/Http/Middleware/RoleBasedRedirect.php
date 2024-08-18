<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleBasedRedirect
{
    protected $roleRedirects = [
        'admin' => '/admin',
        'staff' => '/staff',
        'buyer' => '/buyer',
        'seller' => '/seller',
        'tenant' => '/tenant',
        'landlord' => '/landlord',
        'contractor' => '/contractor',
    ];

    public function handle(Request $request, Closure $next)
    {
        if ($this->isInTenantContext($request)) {
            return $next($request);
        }

        if (Auth::check()) {
            $user = Auth::user();
            foreach ($this->roleRedirects as $role => $redirect) {
                if ($user->hasRole($role)) {
                    if ($request->is($redirect) || $request->is($redirect . '/*')) {
                        return $next($request);
                    }
                   if ($this->shouldRedirect($request, $redirect)) {
                       return redirect($redirect);
                   }
                }
            }
            // If user has a role not in $roleRedirects, redirect to /{role}
            $userRoles = $user->getRoleNames();
            if ($userRoles->isNotEmpty()) {
                $firstRole = $userRoles->first();
                $roleRedirect = '/' . $firstRole;
                if ($request->is($roleRedirect) || $request->is($roleRedirect . '/*')) {
                    return $next($request);
                }
                if ($this->shouldRedirect($request, $roleRedirect)) {
                    return redirect($roleRedirect);
                }
            }
        return $next($request);

        }
        return $next($request);
        // If not authenticated, redirect to login
//        return redirect()->route('login');
    }

  protected function isInTenantContext(Request $request)
    {
        // Check if the current route is already prefixed with a tenant identifier
        // This might need to be adjusted based on your exact tenancy implementation
        return $request->segment(1) === 'tenant' || $request->is('tenant/*');
    }
    protected function shouldRedirect(Request $request, $redirect)
    {
        // Check if the current request path matches the redirect path
        return !$request->is($redirect) && !$request->is($redirect . '/*');
    }
}
