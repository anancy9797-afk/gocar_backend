<?php

namespace Modules\Trip\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Trip\Models\Trip;
use Illuminate\Support\Facades\DB;
class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index()
{
    $rides = Trip::with('driver')
        ->where('client_id', Auth::id())
        ->latest()
        ->get();



    return response()->json([
        'status' => true,
        'data' => $rides,
    ]);
}

    /**
     * Show the form for creating a new resource.
     */
public function create(Request $request)
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

    $trip = DB::transaction(function () use ($validated) {

        $trip = Trip::create([
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
$trip->statusHistory()->create([
    'status' => 'pending',
    'changed_at' => now(),
]),
            'requested_at' => now(),
        ]);

        $trip->statusHistory()->create([
            'status' => 'pending',
            'changed_at' => now(),
        ]);

        return $trip;
    });

    return response()->json([
        'status' => true,
        'message' => 'تم إنشاء الرحلة بنجاح',
        'data' => $trip->load('statusHistory'),
    ], 201);
}


    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'car_type_id' => 'required|integer|exists:vehicle_types,id',

        'pickup_address' => 'required|string|max:255',
        'pickup_latitude' => 'nullable|numeric|between:-90,90',
        'pickup_longitude' => 'nullable|numeric|between:-180,180',
        'price' => 'nullable|numeric',
        'distance' => 'nullable|numeric|between:-180,180',

        'destination_address' => 'required|string|max:255',
        'destination_latitude' => 'nullable|numeric|between:-90,90',
        'destination_longitude' => 'nullable|numeric|between:-180,180',
    ]);

    $trip = DB::transaction(function () use ($validated) {

        $trip = Trip::create([
            'client_id' => Auth::id(),
            'driver_id' => null,
            'car_type_id' => $validated['car_type_id'],

            'pickup_address' => $validated['pickup_address'],
            'pickup_latitude' => $validated['pickup_latitude'] ?? null,
            'pickup_longitude' => $validated['pickup_longitude'] ?? null,

            'destination_address' => $validated['destination_address'],
            'destination_latitude' => $validated['destination_latitude'] ?? null,
            'destination_longitude' => $validated['destination_longitude'] ?? null,

            'distance' => $validated['distance'],
            'price' =>  $validated['price'],
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        // Save the initial status in the history table.
        $trip->statusHistory()->create([
            'status' => 'pending',
            'changed_at' => now(),
        ]);

        return $trip;
    });

    return response()->json([
        'status' => true,
        'message' => 'تم إنشاء الرحلة بنجاح',
        'data' => $trip->load('statusHistory'),
    ], 201);
}


public function pendingTrips()
{
    $trips = Trip::where('status', 'pending')
        ->whereNull('driver_id')
        ->latest()
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'Pending trips retrieved successfully',
        'count' => $trips->count(),
        'data' => $trips,
    ]);
}

    /**
     * Show the specified resource.
     */

        public function allTrips()
    {
$trips = Trip::with('driver')
    ->latest()
    ->get();
        return response()->json([
            'status' => true,
            'message' => 'All trips retrieved successfully.',
            'count' => $trips->count(),
            'data' => $trips,
        ], 200);
    }



    public function acceptTrip(Request $request, $id)
{
    $driver = $request->user();

    if (!$driver) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }

    return DB::transaction(function () use ($driver, $id) {
        $trip = Trip::where('id', $id)
            ->lockForUpdate()
            ->first();

        if (!$trip) {
            return response()->json([
                'status' => false,
                'message' => 'Trip not found.',
            ], 404);
        }

        if (
            $trip->status !== 'pending' ||
            $trip->driver_id !== null
        ) {
            return response()->json([
                'status' => false,
                'message' => 'This trip is no longer available.',
            ], 409);
        }

        $trip->driver_id = $driver->id;
        $trip->status = 'accepted';
        $trip->save();

        return response()->json([
            'status' => true,
            'message' => 'Trip accepted successfully.',
            'data' => $trip->fresh(),
        ], 200);
    });
}
    public function show($id)
    {
        return view('trip::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('trip::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
