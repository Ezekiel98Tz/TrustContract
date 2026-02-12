<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $personal = $user ? $user->profileCompletion() : null;
        $business = null;
        if ($user) {
            try {
                $biz = $user->business;
                if ($biz) {
                    $business = $biz->completion();
                }
            } catch (\Throwable $e) {}
        }
        $overall = null;
        if ($personal || $business) {
            $parts = [];
            if ($personal) $parts[] = $personal['percent'] ?? 0;
            if ($business) $parts[] = $business['percent'] ?? 0;
            $overall = count($parts) ? (int) round(array_sum($parts) / count($parts)) : null;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'trust' => $user ? [
                'verification_level' => $user->verification_level ?? 'none',
                'personal' => $personal,
                'business' => $business,
                'overall' => $overall,
            ] : null,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'status' => $request->session()->get('status'),
            ],
            'notifications' => [
                'unread_count' => $request->user() ? $request->user()->unreadNotifications()->count() : 0,
            ],
        ];
    }
}
