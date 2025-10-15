<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'only' => ['nullable', 'in:all,unread'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $user->notifications()->latest();
        if (($validated['only'] ?? null) === 'unread') {
            $query->whereNull('read_at');
        }

        $perPage = $validated['per_page'] ?? 15;
        return response()->json($query->paginate($perPage));
    }

    public function markRead(Request $request, $id)
    {
        $user = $request->user();

        // IDs are UUID strings; basic validation
        if (!is_string($id) || !Str::isUuid($id)) {
            return response()->json(['message' => 'Invalid notification id'], 422);
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if (!$notification) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (!$notification->read_at) {
            $notification->read_at = now();
            $notification->save();
        }

        return response()->json(['notification' => $notification]);
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();

        $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $count = $user->unreadNotifications()->count();
        return response()->json(['count' => $count]);
    }
}