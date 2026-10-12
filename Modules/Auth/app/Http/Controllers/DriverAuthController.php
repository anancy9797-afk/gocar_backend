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
        $driver = $request->user(); // تأكد من ربط الـ Guard الخاص بالـ Driver في Sanctum

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],

            'vehicle_number' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

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

        $driver->update([
            'name' => $request->name,
            'image' => $imagePath,
                        'email' => $request->email,

            'vehicle_number' => $request->vehicle_number,
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
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    // مفتاح الكاش يعتمد على معرف السائق
    $cacheKey = 'pending_driver_update_' . $driver->id;
    
    $imagePath = $driver->image;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('drivers_temp', 'public');
    }

    $pendingData = [
        'name' => $request->name,  
        'email' => $request->email,

        'phone' => $request->phone,
        'vehicle_number' => $request->vehicle_number,
        'image' => $imagePath,
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