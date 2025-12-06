<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Events\DevicesEvent;

class DeviceController extends Controller
{
    public function get()
    {
        $devices = Device::all();
        return response()->json($devices);
    }
    
    public function invoke(Request $request, String $deviceId)
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
                "message" => "Invoked device control."
            ], 200);
        }
    }

    public function register(Request $request)
    {
        return DB::transaction(function() use ($request) {
            if (!Device::where('key', '=', $request->key)->exists()) {
                $device = new Device;
                $device->nickname = $request->nickname;
                // Some devices may have UUID already, so let's assume that's a sane UUID to use.
                if (($request->key != 'unknown') || ($request->key != null)) {
                    $device->key = $request->key;
                } else {
                    $device->key = Str::uuid();
                }
                $device->device_type = $request->device_type;
                $device->current_song = $request->current_song;
                $device->save();
        
                return response()->json([
                    "message" => "Device registered.",
                    "uuid" => $device->key
                ], 201);
            } else {
                return response()->json([
                    "message" => "Device already registered."
                ], 200);
            }
        });
    }
}
