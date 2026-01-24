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

    public function getPermission(Request $request)
    {
        $key = $request->header('X-Device-Key');
        $device = Device::where('key', $key)->first();

        if (! $device) {
            return response()->json([
                'message' => 'Device not found.',
            ], 404);
        }

        return response()->json([
            'message' => $device->permission,
        ], 200);
    }

    public function setPermission(Request $request, string $deviceKey)
    {
        $requesterKey = $request->header('X-Device-Key');
        $requester = Device::where('key', $requesterKey)->first();

        if (!$requester || (string)$requester->permission !== '111') {
            return response()->json([
                'message' => 'Forbidden: Only superusers can set permissions.',
            ], 403);
        }

        $validData = $request->validate([
            'permission' => 'required|string|regex:/^[0-1]{3}$/',
        ]);

        $device = Device::where('key', $deviceKey)->first();
        if (! $device) {
            return response()->json([
                'message' => 'Device not found.',
            ], 404);
        }

        $device->permission = $validData['permission'];
        $device->save();

        return response()->json([
            'message' => 'Permission updated.',
            'device_key' => $device->key,
            'permission' => $device->permission,
        ], 200);
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

            if (in_array($request->ip(), ['127.0.0.1', '::1'], true)) {
                $device->permission = '111';
            } else {
                $device->permission = '100';
            }

            $device->key = $validKey ? $rawKey : (string) Str::uuid();

            $device->save();

            return response()->json([
                'message' => 'Device registered.',
                'uuid' => $device->key,
            ], 201);
        });
    }
}
