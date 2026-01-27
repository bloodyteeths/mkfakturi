<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Silber\Bouncer\Bouncer;
use Symfony\Component\HttpFoundation\Response;

class ScopeBouncer
{
    /**
     * The Bouncer instance.
     *
     * @var \Silber\Bouncer\Bouncer
     */
    protected $bouncer;

    /**
     * Constructor.
     */
    public function __construct(Bouncer $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    /**
     * Set the proper Bouncer scope for the incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super Admin Support Mode: Override company scope
        if ($user && $user->role === 'super admin') {
            $supportMode = session('support_mode');
            if ($supportMode && isset($supportMode['company_id'])) {
                $this->bouncer->scope()->to($supportMode['company_id']);

                return $next($request);
            }
        }

        // Get company ID from header or use first company
        $tenantId = $request->header('company');

        if (! $tenantId) {
            // Try to use loaded companies first to avoid query
            if ($user->relationLoaded('companies') && $user->companies->isNotEmpty()) {
                $tenantId = $user->companies->first()->id;
            } else {
                // Fallback to query if not loaded
                $tenantId = $user->companies()->first()?->id;
            }
        }

        if ($tenantId) {
            $this->bouncer->scope()->to($tenantId);
        }

        return $next($request);
    }
}
