<?php

namespace Modules\Driver\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver; // لا تنسَ استدعاء نموذج السائق
use Illuminate\Support\Facades\DB;
use Modules\Trip\Models\Trip;
class DriverController extends Controller
{
    /**
     * جلب جميع السائقين للـ Admin (مع دعم البحث والتصفية والـ Pagination)
     */
        public function inactiveDrivers()
    {
        $drivers = Driver::where('is_active', 0)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Inactive drivers retrieved successfully',
            'count' => $drivers->count(),
            'data' => $drivers,
        ], 200);
    }
    public function toggleAvailability(Request $request)
{
    $request->validate([
        'is_available' => 'required|boolean',
    ]);

    $driver = $request->user();

    $driver->is_available = $request->boolean('is_available');
    $driver->save();

    return response()->json([
        'success' => true,
        'message' => 'Driver availability updated successfully',
        'data' => [
            'is_available' => $driver->is_available,
        ],
    ]);
}
    public function index(Request $request)
    {
        // 1. التأكد أن المستخدم الحالي هو Admin
  

        // 2. بناء الاستعلام من جدول السائقين
        $query = Driver::query();

        // 3. تفعيل البحث بالاسم، رقم الهاتف، أو رقم المركبة
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('vehicle_number', 'like', "%{$search}%");
            });
        }

        // 4. تصفية النتائج حسب حالة الاعتماد (اختياري: ?is_active=0 أو ?is_active=1)
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 5. تحديد عدد العناصر في كل صفحة (الافتراضي 15)
        $perPage = $request->get('per_page', 15);
        
        // 6. جلب البيانات وترتيبها من الأحدث
        $drivers = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'تم جلب قائمة السائقين بنجاح',
            'data' => $drivers,
        ], 200);
    }
        public function show($id)
    {
    $driver = Driver::with('carType')->find($id);

        if (!$driver) {
            return response()->json([
                'status' => false,
                'message' => 'Driver not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Driver retrieved successfully',
            'data' => $driver,
        ], 200);
    }




    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('driver::create');
    }



    public function approve($id)
{
    $driver = Driver::find($id);

    if (!$driver) {
        return response()->json([
            'status' => false,
            'message' => 'Driver not found',
        ], 404);
    }

    $driver->is_active = 1;
    $driver->rejection_reason = null;
    $driver->save();

    return response()->json([
        'status' => true,
        'message' => 'Driver accepted successfully',
        'data' => $driver,
    ]);
}

public function reject(Request $request, $id)
{
    $validated = $request->validate([
        'rejection_reason' => 'required|string|min:3|max:1000',
    ]);

    $driver = Driver::find($id);

    if (!$driver) {
        return response()->json([
            'status' => false,
            'message' => 'Driver not found',
        ], 404);
    }

    $driver->is_active = 0;
    $driver->rejection_reason = $validated['rejection_reason'];
    $driver->save();

    return response()->json([
        'status' => true,
        'message' => 'Driver rejected successfully',
        'data' => $driver,
    ]);
}
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}
    /**
     * Store a newly created resource in storage.
     */
public function arrivedAtPickup(Request $request, $id)
{
    $driver = $request->user();

    $result = DB::transaction(function () use ($id, $driver) {

        $trip = Trip::where('id', $id)
            ->lockForUpdate()
            ->first();

        if (!$trip) {
            return [
                'success' => false,
                'message' => 'Trip not found',
                'code' => 404,
            ];
        }

        // Make sure this trip belongs to the authenticated driver
        if ($trip->driver_id != $driver->id) {
            return [
                'success' => false,
                'message' => 'You are not authorized to update this trip',
                'code' => 403,
            ];
        }

        // Only accepted trips can become arrived
        if ($trip->status !== 'accepted') {
            return [
                'success' => false,
                'message' => 'Only accepted trips can be marked as arrived',
                'code' => 422,
            ];
        }

        $trip->status = 'arrived_pickup';
        $trip->save();

        return [
            'success' => true,
            'message' => 'Driver arrived at pickup location successfully',
            'code' => 200,
            'data' => $trip,
        ];
    });

    return response()->json($result, $result['code']);
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('driver::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
    public function updateLocation(Request $request)
{
    $request->validate([
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
    ]);

    $driver = $request->user(); // Authenticated driver via Sanctum/Passport
    
    $driver->update([
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
        'last_location_updated_at' => now(),
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Location updated successfully'
    ]);
}


public function startTrip(Request $request, $id)
{
    $driver = $request->user();

    $result = DB::transaction(function () use ($id, $driver) {

        $trip = Trip::where('id', $id)
            ->lockForUpdate()
            ->first();

        if (!$trip) {
            return [
                'success' => false,
                'message' => 'Trip not found',
                'code' => 404,
            ];
        }

        // Verify that this trip belongs to the authenticated driver
        if ($trip->driver_id != $driver->id) {
            return [
                'success' => false,
                'message' => 'You are not authorized to start this trip',
                'code' => 403,
            ];
        }

        // Only arrived trips can be started
        if ($trip->status !== 'arrived_pickup') {
            return [
                'success' => false,
                'message' => 'Only trips that have arrived at pickup can be started',
                'code' => 422,
            ];
        }

        $trip->status = 'in_progress';
        $trip->started_at = now();
        $trip->save();

        return [
            'success' => true,
            'message' => 'Trip started successfully',
            'code' => 200,
            'data' => $trip,
        ];
    });

    return response()->json($result, $result['code']);
}
public function endTrip(Request $request, $id)
{
    $driver = $request->user();

    $result = DB::transaction(function () use ($id, $driver) {

        $trip = Trip::where('id', $id)
            ->lockForUpdate()
            ->first();

        if (!$trip) {
            return [
                'success' => false,
                'message' => 'Trip not found',
                'code' => 404,
            ];
        }

        // Verify that this trip belongs to the authenticated driver
        if ($trip->driver_id != $driver->id) {
            return [
                'success' => false,
                'message' => 'You are not authorized to start this trip',
                'code' => 403,
            ];
        }

        // Only arrived trips can be started
        if ($trip->status !== 'in_progress') {
            return [
                'success' => false,
                'message' => 'Only trips that are in progress can be ended',
                'code' => 422,
            ];
        }

        $trip->status = 'completed';
        $trip->completed_at = now();
        $trip->save();

        return [
            'success' => true,
            'message' => 'Trip ended successfully',
            'code' => 200,
            'data' => $trip,
        ];
    });

    return response()->json($result, $result['code']);
}
public function markTripAsPaid(Request $request, $id)
{
    $driver = $request->user();

    $result = DB::transaction(function () use ($id, $driver) {

        $trip = Trip::where('id', $id)
            ->lockForUpdate()
            ->first();

        if (!$trip) {
            return [
                'success' => false,
                'message' => 'Trip not found',
                'code' => 404,
            ];
        }

        // Verify that this trip belongs to the authenticated driver
        if ($trip->driver_id != $driver->id) {
            return [
                'success' => false,
                'message' => 'You are not authorized to start this trip',
                'code' => 403,
            ];
        }

        // Only arrived trips can be started
        if ($trip->status !== 'completed') {
            return [
                'success' => false,
                'message' => 'Only completed trips can be marked as paid',
                'code' => 422,
            ];
        }

        $trip->status = 'paid';
        $trip->completed_at = now();
        $trip->save();

        return [
            'success' => true,
            'message' => 'Trip marked as paid successfully',
            'code' => 200,
            'data' => $trip,
        ];
    });

    return response()->json($result, $result['code']);
}
public function getMyWallet(Request $request)
{
    $driver = $request->user();

    $wallet = $driver->wallet()->firstOrCreate(
        [],
        ['balance' => 0]
    );

    return response()->json([
        'success' => true,
        'message' => 'Wallet balance retrieved successfully',
        'data' => [
            'balance' => $wallet->balance,
            'currency' => 'EGP',
        ],
    ]);
}

}