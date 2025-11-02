<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Devices;
use App\Events\DevicesEvent;

class DeviceController extends Controller
{
    public function get()
    {
        $devices = Devices::all();
        return response()->json($devices);
    }
    
    public function invoke(Request $request, String $deviceId)
    {
        if (Devices::where('key', '=', $deviceId)->exists()) {
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
        DB::transaction(function() use ($request) {
            if (!Devices::where('key', '=', $request->key)->exists()) {
                $device = new Devices;
                $device->nickname = $request->nickname;
                $device->key = $request->key;
                $device->device_type = $request->device_type;
                $device->current_song = $request->current_song;
                $device->save();
        
                return response()->json([
                    "message" => "Device registered."
                ], 201);
            } else {
                return response()->json([
                    "message" => "Device already registered."
                ], 200);
            }
        });
    }
}
