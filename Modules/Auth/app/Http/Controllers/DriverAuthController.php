<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver; // نموذج السائق الجديد
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
class DriverAuthController extends Controller
{
    /**
    
    
     * تسجيل أو التحقق من رقم هاتف السائق
     */
    public function checkPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $phone = $request->phone;

        // البحث في جدول السائقين فقط
        $driver = Driver::where('phone', $phone)->first();

        if (!$driver) {
            $driver = Driver::create([
                'phone' => $phone,
                'is_active' => 0,
            ]);
            $exists = false;
            $message = 'تم إنشاء حساب سائق جديد، تم إرسال رمز التحقق';
        } else {
            $exists = true;
            $message = 'تم إرسال رمز التحقق لتسجيل الدخول';
        }

        $otp = '9999'; // مؤقت
        $token = $driver->createToken('DriverToken')->plainTextToken;

        return response()->json([
            'status' => true,
            'exists' => $exists,
            'message' => $message,
            'token' => $token,
            'otp' => $otp,
            'driver' => $driver,
        ]);
    }

    /**
     * استكمال بيانات السائق
     */
public function completeProfile(Request $request)
    {
      
        $driver = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:drivers,email,' . $driver->id], // Fixed to drivers table and ignore current id
            'image' => ['nullable', 'file'], // Changed from 'image' to 'file'
    'vehicle_image' => ['nullable', 'file'], // Changed from 'image' to 'file'
            // 'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            // 'vehicle_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'vehicle_number' => ['nullable', 'string'],
            'car_type_id' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Handle Profile Image
        $imagePath = $driver->image;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            $destinationPath = public_path('uploads/profiles_temp');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0775, true);
            }

            $file->move($destinationPath, $filename);
            $imagePath = 'uploads/profiles_temp/' . $filename;
        }

        // Handle Vehicle Image
        $vehicleImagePath = $driver->vehicle_image;
        if ($request->hasFile('vehicle_image')) {
            $vehicleFile = $request->file('vehicle_image');
            $vehicleFilename = 'vehicle_' . time() . '_' . uniqid() . '.' . $vehicleFile->getClientOriginalExtension();

            $vehicleDestinationPath = public_path('uploads/vehicles_temp');
            if (!file_exists($vehicleDestinationPath)) {
                mkdir($vehicleDestinationPath, 0775, true);
            }

            $vehicleFile->move($vehicleDestinationPath, $vehicleFilename);
            $vehicleImagePath = 'uploads/vehicles_temp/' . $vehicleFilename;
        }

        $driver->update([
            'name' => $request->name,
            'email' => $request->email,
            'image' => $imagePath,
            'vehicle_image' => $vehicleImagePath,
            'vehicle_number' => $request->vehicle_number,
            'car_type_id' => $request->car_type_id,
            'is_active' => 0,
        ]);

        $driver->refresh();

        return response()->json([
            'status' => true,
            'message' => 'تم استكمال بيانات السائق بنجاح',
            'driver' => $driver,
        ]);
    }

public function requestUpdateProfile(Request $request)
{
    $driver = $request->user();

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $driver->id],
        'phone' => ['required', 'string', 'max:20', 'unique:drivers,phone,' . $driver->id],
        'vehicle_number' => ['nullable', 'string', 'max:50'],
        'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        'vehicle_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
                    'car_type_id' => ['nullable', 'string'],

    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    // مفتاح الكاش يعتمد على معرف السائق
    $cacheKey = 'pending_driver_update_' . $driver->id;
    
// Handle Profile Image
$imagePath = $driver->image;

if ($request->hasFile('image')) {
    $file = $request->file('image');

    $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

    $destinationPath = public_path('uploads/profiles_temp');

    if (!file_exists($destinationPath)) {
        mkdir($destinationPath, 0775, true);
    }

    $file->move($destinationPath, $filename);
\Log::info('Vehicle image uploaded', [
    'filename' => $filename,
    'path' => $vehicleImagePath,
    'exists' => file_exists($destinationPath . '/' . $filename),
]);
    $imagePath = 'uploads/profiles_temp/' . $filename;
}


// Handle Vehicle Image
$vehicleImagePath = $driver->vehicle_image;

if ($request->hasFile('vehicle_image')) {
    $file = $request->file('vehicle_image');

    $filename = 'vehicle_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

    $destinationPath = public_path('uploads/vehicles_temp');

    if (!file_exists($destinationPath)) {
        mkdir($destinationPath, 0775, true);
    }

    $file->move($destinationPath, $filename);

    $vehicleImagePath = 'uploads/vehicles_temp/' . $filename;
}

    $pendingData = [
        'name' => $request->name,  
        'email' => $request->email,

        'phone' => $request->phone,
        'vehicle_number' => $request->vehicle_number,
        'image' => $imagePath,
        'vehicle_image' => $vehicleImagePath,
        'car_type_id' => $request->car_type_id,
    ];

    // تخزين البيانات في الـ Cache لمدة 10 دقائق لحين إدخال رمز التحقق
    \Illuminate\Support\Facades\Cache::put($cacheKey, $pendingData, now()->addMinutes(10));

    // رمز التحقق المؤقت
    $otp = '9999';

    return response()->json([
        'status' => true,
        'message' => "Verification code sent to confirm data update",
        'otp' => $otp, // للتجربة فقط
    ], 200);
}

    /**
     * التحقق من الرمز وتطبيق التعديل نهائياً
     */
/**
     * التحقق من الرمز وتطبيق التعديل نهائياً للسائق
     */
    public function verifyAndUpdateProfile(Request $request)
    {
        $driver = $request->user();

        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // التحقق من صحة الرمز
        if ($request->otp !== '9999') {
            return response()->json([
                'status' => false,
                'message' => "Invalid verification code",
            ], 422);
        }

        $cacheKey = 'pending_driver_update_' . $driver->id;

        // جلب البيانات المؤقتة
        if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return response()->json([
                'status' => false,
                'message' => "The update request has expired, please try again",
            ], 422);
        }

        $pendingData = \Illuminate\Support\Facades\Cache::get($cacheKey);

        // تحديث البيانات الحقيقية في قاعدة البيانات للسائق
        $driver->update($pendingData);
        $driver->refresh();

        // حذف الكاش بعد إتمام التعديل بنجاح
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        return response()->json([
            'status' => true,
            'message' => "Profile successfully updated after verification",
            'driver' => $driver,
        ], 200);
    }
public function me(Request $request)
    {
        $driver = $request->user(); // يحضر بيانات السائق الحالي من توكن Sanctum

        if (!$driver) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح أو السائق غير موجود',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'userInfo' => $driver,
        ]);
    }
}