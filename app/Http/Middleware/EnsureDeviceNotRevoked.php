<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Schema;

class EnsureDeviceNotRevoked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user) {
            if (Schema::hasTable('user_devices')) {
                $fp = hash('sha256', ($request->ip() ?? 'unknown') . '|' . ($request->header('User-Agent') ?? 'unknown'));
                $device = UserDevice::where('user_id', $user->id)->where('fingerprint_hash', $fp)->first();
                if ($device && $device->revoked_at) {
                    auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect()->route('login')->with('status', 'device-revoked');
                }
                if ($device && !$device->revoked_at) {
                    $device->last_seen_at = now();
                    $device->ip_address = (string) $request->ip();
                    $device->user_agent = (string) $request->header('User-Agent');
                    $device->save();
                }
            }
        }
        return $next($request);
    }
}
