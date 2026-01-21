<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->paginate(15);
        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications->through(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->data['type'] ?? 'info',
                    'message' => $n->data['message'] ?? '',
                    'read_at' => $n->read_at,
                    'created_at' => $n->created_at,
                ];
            }),
        ]);
    }

    public function read(Request $request, $id)
    {
        $user = $request->user();
        $n = $user->notifications()->where('id', $id)->firstOrFail();
        if (!$n->read_at) {
            $n->read_at = now();
            $n->save();
        }
        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    }
}
