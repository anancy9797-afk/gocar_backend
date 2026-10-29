<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admins')->insert([
            'name' => 'Admin Name',
            'phone' => '01011112222',
            'email' => 'admin2@gmail.com',
            'password' => Hash::make('123456'), // يقوم بتشفير كلمة المرور تلقائياً بالشكل الصحيح
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}