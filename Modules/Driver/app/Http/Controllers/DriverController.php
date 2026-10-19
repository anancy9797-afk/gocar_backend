<?php

namespace Modules\Driver\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver; // لا تنسَ استدعاء نموذج السائق

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
}