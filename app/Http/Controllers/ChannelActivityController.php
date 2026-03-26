<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ChannelActivityController extends Controller
{
    public function ping(Request $request)
    {
        $channel = $request->input('channel');

        if (empty($channel) || !str_starts_with($channel, 'trip.')) {
            return response()->json(['error' => 'Invalid channel'], 422);
        }

        // Cache frissítése 2 percre
        $cacheKey = "channel_activity:{$channel}";
        Cache::put($cacheKey, time(), 120);

        Log::debug("Channel activity ping received for: {$channel} | User ID: " . $request->user()->id);

        return response()->json([
            'status' => 'ok',
            'channel' => $channel,
            'expires_in' => 120
        ]);
    }
}