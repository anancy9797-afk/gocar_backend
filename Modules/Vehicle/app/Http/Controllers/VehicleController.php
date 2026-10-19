<?php

namespace Modules\Vehicle\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Vehicle\Models\VehicleType;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */

public function index(Request $request)
{
    try {
        $query = VehicleType::query();

        // Search by name or description
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Pagination
        $perPage = max(1, min((int) $request->get('per_page', 15), 100));

        $vehicles = $query->latest()->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'تم جلب قائمة المركبات بنجاح',
            'data' => $vehicles,
        ], 200);

    }catch (\Throwable $e) {
    return response()->json([
        'status' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ], 500);
}

}


    /**
     * إضافة نوع مركبة جديد (الاسم، الوصف، السعر لكل كيلو، والصورة)
     */
    public function store(Request $request)
    {
        // 1. التحقق من صحة البيانات المرسلة
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:vehicle_types,name'],
            'description' => ['nullable', 'string'],
            'price_per_kilo' => ['required', 'numeric', 'min:0'], // السعر لكل كيلو
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // الصورة
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // 2. معالجة وحفظ الصورة في مجلد فيزيائي آمن داخل public لتجنب أخطاء 403
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            $destinationPath = public_path('uploads/vehicle_types');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0775, true);
            }
            
            $file->move($destinationPath, $filename);
            $imagePath = 'uploads/vehicle_types/' . $filename;
        }

        // 3. حفظ البيانات في قاعدة البيانات
        $vehicleType = VehicleType::create([
            'name' => $request->name,
            'description' => $request->description,
            'price_per_kilo' => $request->price_per_kilo,
            'image' => $imagePath,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Vehicle type added successfully',
            'data' => $vehicleType,
        ], 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('vehicle::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('vehicle::edit');
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

