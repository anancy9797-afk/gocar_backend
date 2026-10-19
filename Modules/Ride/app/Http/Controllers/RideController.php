<?php

namespace Modules\Ride\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Ride\Models\Ride;

class RideController extends Controller
{
    /**
     * Client creates a new ride.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_type_id' => 'required|integer|exists:vehicle_types,id',

            'pickup_address' => 'required|string|max:255',
            'pickup_latitude' => 'nullable|numeric|between:-90,90',
            'pickup_longitude' => 'nullable|numeric|between:-180,180',

            'destination_address' => 'required|string|max:255',
            'destination_latitude' => 'nullable|numeric|between:-90,90',
            'destination_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $ride = Ride::create([
            'client_id' => Auth::id(),
            'driver_id' => null,
            'car_type_id' => $validated['car_type_id'],

            'pickup_address' => $validated['pickup_address'],
            'pickup_latitude' => $validated['pickup_latitude'] ?? null,
            'pickup_longitude' => $validated['pickup_longitude'] ?? null,

            'destination_address' => $validated['destination_address'],
            'destination_latitude' => $validated['destination_latitude'] ?? null,
            'destination_longitude' => $validated['destination_longitude'] ?? null,

            'distance' => null,
            'price' => null,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Ride created successfully.',
            'data' => $ride,
        ], 201);
    }

    public function index()
    {
        $rides = Ride::where('client_id', Auth::id())
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $rides,
        ]);
    }

    public function show($id)
    {
        $ride = Ride::where('client_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $ride,
        ]);
    }
}
