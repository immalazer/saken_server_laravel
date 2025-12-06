<?php

namespace App\Http\Controllers;

use App\Events\DevicesEvent;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function get()
    {
        $devices = Device::all();

        return response()->json($devices);
    }

    public function invoke(Request $request, string $deviceId)
    {
        if (Device::where('key', '=', $deviceId)->exists()) {
            $reason = $request->reason;
            $origin = $request->origin;
            $key = $deviceId;
            $filename = $request->filename;
            $extra = $request->extra;

            if ($deviceId != $origin) {
                event(new DevicesEvent($reason, $origin, $key, $filename, $extra));
            }

            return response()->json([
                'message' => 'Invoked device control.',
            ], 200);
        }
    }

    public function register(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validData = $request->validate([
                'nickname' => 'required|string|max:255',
                'device_type' => 'required|string|max:255',
                'current_song' => 'nullable|string',
                'key' => 'nullable|string',
            ]);

            // Normalize key here. Anything that isn't a valid UUID is thrown away.
            $rawKey = $validData['key'] ?? null;
            $validKey = $rawKey !== null && $rawKey !== '' && $rawKey !== 'unknown';

            if (Device::where('key', '=', $rawKey)->exists()) {
                return response()->json([
                    'message' => 'Device already registered.',
                    'uuid' => $rawKey,
                ], 200);
            }

            $device = new Device;
            $device->nickname = $validData['nickname'];
            $device->device_type = $validData['device_type'];
            $device->current_song = $validData['current_song'] ?? null;

            $device->key = $validKey ? $rawKey : (string) Str::uuid();

            $device->save();

            return response()->json([
                'message' => 'Device registered.',
                'uuid' => $device->key,
            ], 201);
        });
    }
}
