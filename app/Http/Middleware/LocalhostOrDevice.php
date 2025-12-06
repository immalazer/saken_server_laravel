<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;

class LocalhostOrDevice
{
    public function handle(Request $request, Closure $next)
    {
        // We generally want anything in localhost to pass.
        // If localhost is compromised then that's not really our issue.
        if (in_array($request->ip(), ['127.0.0.1', '::1'], true)) {
            return $next($request);
        }

        // Check for valid X-Device-Key in header.
        $key = $request->header('X-Device-Key');
        if ($key && Device::where('key', $key)->exists()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Forbidden: Unauthorised.',
        ], 403);
    }
}