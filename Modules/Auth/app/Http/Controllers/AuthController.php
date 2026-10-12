<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('auth::index');
    }
public function requestUpdateProfile(Request $request)
{
    $user = $request->user();

    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
        'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors(),
        ], 422);
    }

    $cacheKey = 'pending_profile_update_' . $user->id;
    
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

    $pendingData = [
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'image' => $imagePath,
    ];

    Cache::put($cacheKey, $pendingData, now()->addMinutes(10));

    $otp = '9999';

    return response()->json([
        'status' => true,
        'message' => "Verification code sent to confirm data update",
        'otp' => $otp,
    ], 200);
}    
    
public function verifyAndUpdateProfile(Request $request)
{
    $user = $request->user();

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
            'message' =>"Invalid verification code",
        ], 422);
    }

    $cacheKey = 'pending_profile_update_' . $user->id;

    // جلب البيانات المؤقتة
    if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
        return response()->json([
            'status' => false,
            'message' =>"The update request has expired, please try again",
        ], 422);
    }

    $pendingData = \Illuminate\Support\Facades\Cache::get($cacheKey);

    // تحديث البيانات الحقيقية في القاعدة
    $user->update($pendingData);
    $user->refresh();

    // حذف الكاش بعد إتمام التعديل بنجاح
    \Illuminate\Support\Facades\Cache::forget($cacheKey);

    return response()->json([
        'status' => true,
        'message' =>"Profile successfully updated after verification",
        'user' => $user,
    ], 200);
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('auth::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('auth::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('auth::edit');
    }
public function me(Request $request)
{
    $user = $request->user();

    return response()->json([
        'status' => true,
        'userInfo' => $user,
    ], 200);
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
