<?php

namespace Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('wallet::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('wallet::create');
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
        return view('wallet::show');
    }
        /**
     * Get wallet by driver ID.
     */
    public function driverWallet($driverId)
    {
        $driver = Driver::with('wallet')->findOrFail($driverId);

        $wallet = $driver->wallet;

        return response()->json([
            'success' => true,
            'message' => 'Driver wallet retrieved successfully',
            'data' => [
                'driver_id' => $driver->id,
                'driver_name' => $driver->name,
                'wallet' => $wallet,
                'balance' => (float) ($wallet?->balance ?? 0),
            ],
        ], 200);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('wallet::edit');
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
