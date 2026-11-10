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
public function getAllTrips(Request $request)
    {
        try {
    
            // جلب جميع الرحلات مع بيانات العميل أو السائق المرتبطة بها (Eager Loading)
            $trips = Trip::with(['client', 'driver', 'carType'])
                         ->latest()
                         ->paginate($request->get('per_page', 15)); // دعم التقسيم (Pagination) إن أردت

            return response()->json([
                'status' => true,
                'message' => 'Trips fetched successfully',
                'data' => $trips
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch trips',
                'error' => $e->getMessage()
            ], 500);
        }
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


// public function allTrips(Request $request)
// {
//     try {
//         $driver = $request->user();

//         $trips = Trip::with(['client', 'driver', 'carType'])
//             ->where(function ($query) use ($driver) {
//                 $query->where('driver_id', $driver->id)
//                     ->orWhere(function ($q) {
//                         $q->whereNull('driver_id')
//                           ->where('status', 'pending');
//                     });
//             })
//             ->latest()
//             ->paginate($request->get('per_page', 15));

//         return response()->json([
//             'status' => true,
//             'message' => 'Trips fetched successfully',
//             'data' => $trips
//         ], 200);

//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Failed to fetch trips',
//             'error' => $e->getMessage()
//         ], 500);
//     }
// }

public function allTrips(Request $request)
{
    try {
        $user = $request->user();

        $request->validate([
            'status' => [
                'nullable',
                'in:pending,accepted,arrived_pickup,in_progress,completed,paid'
            ],
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Trip::with(['client', 'driver', 'carType']);

        if ($user->getTable() === 'drivers') {

            // Driver: own trips + available pending trips
            $query->where(function ($q) use ($user) {
                $q->where('driver_id', $user->id)
                  ->orWhere(function ($subQuery) {
                      $subQuery->whereNull('driver_id')
                               ->where('status', 'pending');
                  });
            });

        } elseif ($user->getTable() === 'users') {

            // Client: only own trips
            $query->where('client_id', $user->id);

        } else {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized user type'
            ], 403);
        }

        // Filter by status
        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $trips = $query->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => true,
            'message' => 'Trips fetched successfully',
            'data' => $trips
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to fetch trips',
            'error' => $e->getMessage()
        ], 500);
    }
}

//         public function allTrips()
//     {
// $trips = Trip::with('client', 'driver', 'carType')
//     ->latest()
//     ->get();
//         return response()->json([
//             'status' => true,
//             'message' => 'All trips retrieved successfully.',
//             'count' => $trips->count(),
//             'data' => $trips,
//         ], 200);
//     }



public function weeklyPaidTrips(Request $request)
{
    try {
        $driver = $request->user();

        // All paid trips (without date filter)
        $allPaidQuery = Trip::where('driver_id', $driver->id)
            ->where('status', 'paid');

        $totalPaidPriceAllTime = (clone $allPaidQuery)->sum('price');

        $totalPaidTripsAllTime = (clone $allPaidQuery)->count();

        // Current week: Monday to Sunday
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        // Current week paid trips
        $query = (clone $allPaidQuery)->whereBetween('created_at', [
            $startOfWeek,
            $endOfWeek
        ]);

        $totalPaidPrice = (clone $query)->sum('price');

        $totalPaidTrips = (clone $query)->count();

        $trips = (clone $query)
            ->with(['client', 'carType'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'status' => true,
            'message' => 'Paid trips summary fetched successfully',
            'data' => [
                'driver_id' => $driver->id,

                // All time
                'total_paid_price_all_time' => (float) $totalPaidPriceAllTime,
                'total_paid_trips_all_time' => $totalPaidTripsAllTime,

                // Current week
                'week_start' => $startOfWeek->toDateString(),
                'week_end' => $endOfWeek->toDateString(),
                'total_paid_price' => (float) $totalPaidPrice,
                'total_paid_trips' => $totalPaidTrips,

                'trips' => $trips
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Failed to fetch paid trips',
            'error' => $e->getMessage()
        ], 500);
    }
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
