<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisputesController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->query('status', '');
        $query = Dispute::query()
            ->with(['contract', 'initiator', 'mediator'])
            ->orderByDesc('created_at');
        if (in_array($status, ['open','mediate','resolved','cancelled'], true)) {
            $query->where('status', $status);
        }
        $disputes = $query->paginate(10)->appends(['status' => $status]);
        return Inertia::render('Admin/Disputes/Index', [
            'disputes' => $disputes,
            'filters' => ['status' => $status ?: null],
        ]);
    }

    public function show(Request $request, Dispute $dispute)
    {
        $payload = [
            'id' => $dispute->id,
            'status' => $dispute->status,
            'resolution' => $dispute->resolution,
            'mediation_notes' => $dispute->mediation_notes,
            'contract' => $dispute->contract ? [
                'id' => $dispute->contract->id,
                'title' => $dispute->contract->title,
            ] : null,
            'initiator' => $dispute->initiator ? [
                'id' => $dispute->initiator->id,
                'name' => $dispute->initiator->name,
            ] : null,
            'mediator' => $dispute->mediator ? [
                'id' => $dispute->mediator->id,
                'name' => $dispute->mediator->name,
            ] : null,
            'logs' => \App\Models\DisputeLog::where('dispute_id', $dispute->id)->latest()->get()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'from_status' => $log->from_status,
                    'to_status' => $log->to_status,
                    'notes' => $log->notes,
                    'created_at' => $log->created_at,
                ];
            }),
        ];
        return Inertia::render('Admin/Disputes/Show', [
            'dispute' => $payload,
        ]);
    }

    public function review(Request $request, Dispute $dispute)
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,mediate,resolved,cancelled'],
            'resolution' => ['nullable', 'in:won,lost,cancelled'],
            'mediator_id' => ['nullable', 'integer', 'exists:users,id'],
            'mediation_notes' => ['nullable', 'string'],
        ]);
        $before = ['status' => $dispute->status, 'mediator_id' => $dispute->mediator_id, 'resolution' => $dispute->resolution];
        $dispute->status = $data['status'];
        if ($data['status'] === 'resolved') {
            $dispute->resolution = $data['resolution'] ?? $dispute->resolution;
            $dispute->resolved_at = now();
        } else {
            $dispute->resolution = null;
            $dispute->resolved_at = null;
        }
        $dispute->mediator_id = $data['mediator_id'] ?? $dispute->mediator_id;
        $dispute->mediation_notes = $data['mediation_notes'] ?? $dispute->mediation_notes;
        $dispute->save();
        try {
            \App\Models\DisputeLog::create([
                'dispute_id' => $dispute->id,
                'actor_id' => $request->user()->id,
                'action' => $before['status'] !== $dispute->status ? 'status_changed' : 'note_updated',
                'from_status' => $before['status'],
                'to_status' => $dispute->status,
                'notes' => $data['mediation_notes'] ?? null,
            ]);
            if ($before['mediator_id'] !== $dispute->mediator_id && $dispute->mediator_id) {
                \App\Models\DisputeLog::create([
                    'dispute_id' => $dispute->id,
                    'actor_id' => $request->user()->id,
                    'action' => 'mediator_assigned',
                    'from_status' => $before['status'],
                    'to_status' => $dispute->status,
                    'notes' => 'Mediator assigned: ' . $dispute->mediator_id,
                ]);
                $mediator = \App\Models\User::find($dispute->mediator_id);
                if ($mediator) {
                    $mediator->notify(new \App\Notifications\MediatorAssignedNotification($dispute));
                }
            }
        } catch (\Throwable $e) {}
        // Notify parties about status change
        try {
            $contract = $dispute->contract;
            foreach ([$contract?->buyer_id, $contract?->seller_id] as $uid) {
                if (!$uid) continue;
                $user = \App\Models\User::find($uid);
                if ($user) {
                    $user->notify(new \App\Notifications\DisputeStatusChangedNotification($dispute));
                }
            }
        } catch (\Throwable $e) {}
        return back()->with('success', 'Dispute status updated.');
    }
}
