<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class DeviceController extends Controller
{
    public function index(Request $request): Response
    {
        $devices = UserDevice::where('user_id', $request->user()->id)
            ->orderByDesc('last_seen_at')
            ->get();

        return Inertia::render('Account/Devices', [
            'devices' => $devices,
        ]);
    }

    public function revoke(Request $request, $id)
    {
        $device = UserDevice::where('user_id', $request->user()->id)->findOrFail($id);
        $device->revoked_at = now();
        $device->save();
        return Redirect::route('account.devices.index')->with('status', 'revoked');
    }
}
