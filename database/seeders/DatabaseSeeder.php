<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);
        
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ])->assignRole('admin');

        User::factory()->create([
            'name' => 'Gestionnaire User',
            'email' => 'gestionnaire@example.com',
        ])->assignRole('manager');

        User::factory()->create([
            'name' => 'Formateur User',
            'email' => 'formateur@example.com',
        ])->assignRole('coach');

        User::factory()->create([
            'name' => 'Apprenant User',
            'email' => 'apprenant@example.com',
        ])->assignRole('student');
    }
}
