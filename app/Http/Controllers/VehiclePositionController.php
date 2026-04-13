<?php

namespace App\Http\Controllers;

use App\Jobs\PollVehiclePosition;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Models\Trip;

class VehiclePositionController extends Controller
{
    public function startPolling(string $tripId): JsonResponse
    {
        try {
            if (empty($tripId)) {
                return response()->json([
                    'error' => 'Helytelen trip ID',
                ], 400);
            }

            Trip::findOrFail($tripId);

            Log::info("Trip helyzetének lekérésének kezdete: {$tripId}");
            PollVehiclePosition::dispatch($tripId);

            return response()->json([
                'message' => 'Trip helyzetének lekérésének elkezdődött',
                'trip_id' => $tripId,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Helytelen trip ID',
            ], 400);
        } catch (\Exception $e) {
            Log::error("Hiba a jármű helyzetének streamelése kezdeténél: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Nem sikerült a lekérés',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
