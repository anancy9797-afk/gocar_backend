<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
class PhoneAuthController extends Controller
{
    /**
     * تسجيل/إنشاء المستخدم عن طريق رقم الهاتف
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

        // البحث عن المستخدم
        $user = User::where('phone', $phone)->first();

        // إنشاء المستخدم إذا لم يكن موجودًا
        if (!$user) {
            $user = User::create([
                'phone' => $phone,
                    'is_active' => 0,

            ]);

            $exists = false;
            $message = 'تم إنشاء حساب جديد، تم إرسال رمز التحقق';
        } else {
            $exists = true;
            $message = 'تم إرسال رمز التحقق';
        }

        // OTP مؤقت للتجربة
        $otp = '9999';

        // إنشاء Token
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'status' => true,
            'exists' => $exists,
            'message' => $message,

            'token' => $token,

            // مؤقتًا أثناء التطوير فقط
            'otp' => $otp,

            'user' => $user,
        ]);
    }

    /**
     * التحقق من OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // OTP ثابت مؤقتًا
        if ($request->otp !== '9999') {
            return response()->json([
                'status' => false,
                'message' => 'رمز التحقق غير صحيح',
            ], 422);
        }
$user = $request->user();

// $user->update([
//     'is_active' => true,
// ]);

        return response()->json([
            'status' => true,
            'message' => 'تم التحقق من رقم الهاتف بنجاح',
            'verified' => true,
            'next' => 'complete_profile',
        ]);
    }



public function completeProfile(Request $request)
{
    $user = $request->user();

    // التأكد أن الحقل محذوف تماماً من الـ Request لكي لا يسبب مشكلة
    $request->request->remove('password'); 
    // أو:
    // unset($request['password']);

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

// رفع الصورة الشخصية أو المستندات إن وجدت
        $imagePath = $user->image;
        if ($request->hasFile('image')) {
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }
            $imagePath = $request->file('image')->store('profiles', 'public');
        }

        // تحديث البيانات حسب الـ Role
        $updateData = [
            'name' => $request->name,
            'image' => $imagePath,
        ];

        if ($user->role === 'user') {
            $updateData['email'] = $request->email;
        } elseif ($user->role === 'driver') {
            // أضف حقول السائق الإضافية هنا إذا كانت مخزنة في جدول الـ users
            // $updateData['vehicle_number'] = $request->vehicle_number;
        }

        $user->update($updateData);
        $user->refresh();

        return response()->json([
            'status' => true,
            'message' => 'تم استكمال بيانات الحساب بنجاح',
            'user' => $user,
        ]);
        }



    public function adminLogin(Request $request)
{
    $request->validate([
        'phone' => ['required', 'string'],
        'password' => ['nullable', 'string'],
    ]);

    // البحث عن المستخدم برقم الهاتف
    $user = Admin::where('phone', $request->phone)->first();

    // التحقق من وجود المستخدم، صحة كلمة المرور، وأنه يمتلك صلاحية admin
    if (!$user || !Hash::check($request->password, $user->password) ) {
        return response()->json([
            'status' => false,
            'message' => 'بيانات الاعتماد غير صحيحة أو ليس لديك صلاحية الدخول كمسؤول.',
        ], 401);
    }

    // إنشاء توكن الدخول (في حال استخدام Laravel Sanctum)
    $token = $user->createToken('AdminToken')->plainTextToken;

    return response()->json([
        'status' => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'token' => $token,
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'role' => $user->role,
        ]
    ], 200);
}

/**
 * جلب جميع العملاء مع البحث والتصفح (Pagination) للأدمن
 */
public function getAllClients(Request $request)
{
    // التأكد أن المستخدم الحالي هو Admin


    // بناء الاستعلام مع جلب العملاء فقط (مثلاً من لديهم role = 'user' أو استثناء الـ admin)
    $query = User::where('role', '!=', 'admin');

    // تفعيل البحث في حال تم إرسال معامل البحث (search)
    if ($request->has('search') && !empty($request->search)) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    // تحديد عدد العناصر في كل صفحة (الافتراضي 15 عنصر، ويمكن التحكم به عبر طلب الـ per_page)
    $perPage = $request->get('per_page', 15);
    
    // جلب البيانات مع الـ Pagination وترتيبهم من الأحدث
    $clients = $query->latest()->paginate($perPage);

    return response()->json([
        'status' => true,
        'message' => 'تم جلب قائمة العملاء بنجاح',
        'data' => $clients,
    ], 200);
}


}
