<?php

namespace Modules\Rooms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Rooms\Models\Device;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated Sanctum tokenable is an active, unrevoked Device.
 *
 * A missing/unknown token already results in 401 from the Sanctum middleware.
 * This middleware adds the 403 check: a known token that belongs to a revoked
 * device is not allowed to proceed.
 */
class EnsureDeviceIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $device = $request->user();

        if (! ($device instanceof Device) || ! $device->isActive()) {
            abort(403, 'This device has been revoked or is not paired to a room.');
        }

        // Update last_seen_at at most once per minute.
        $device->touchLastSeen();

        return $next($request);
    }
}
