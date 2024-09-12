<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Resident;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
       
        Resident::create([
            'reg_number' => 'REG_00000000_01',
            'lname' => 'ADMIN',
            'fname' => 'ADMIN',
            'mname' => 'ADMIN',
            'ext' => 'ADMIN',
            'address' => 'ADMIN',
            'household' => 'ADMIN',
            'Birth' => 'ADMIN',
            'birthday' => 'ADMIN',
            'age' => 'ADMIN',
            'cnum' => 'ADMIN',
            'gender' => 'ADMIN',
            'civil' => 'ADMIN',
            'citizenship' => 'ADMIN',
            'occupation' => 'ADMIN',
            'indicate_if' => 'ADMIN',
            'owner_type' => 'ADMIN',
            'owner_name' => 'ADMIN',
            'number_of_family' => 0,
            'daysofliving' =>'ADMIN',
            'proof_of_owner' => 'ADMIN',
            'voters_filename' => 'ADMIN',
            'valid_id_filename' => 'ADMIN',
            'image_filename' => 'ADMIN',
            'IDtype'=>'ADMIN',
            'email' => 'admin@gmail.com', // Assuming this is unique
            'password' => 'Admin@2305', // Hashed password
            'status' => 'Admin',
        ]); //
    }
}
