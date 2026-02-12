<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use Illuminate\Http\Request;

class DisputeMessagesController extends Controller
{
    public function store(Request $request, Dispute $dispute)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);
        $msg = DisputeMessage::create([
            'dispute_id' => $dispute->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);
        try {
            \App\Models\DisputeLog::create([
                'dispute_id' => $dispute->id,
                'actor_id' => $request->user()->id,
                'action' => 'message_posted',
                'notes' => $msg->body,
            ]);
        } catch (\Throwable $e) {}
        return back()->with('success', 'Message posted.');
    }
}
