<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        // Admin permissions
        Permission::firstOrCreate(['name' => 'manage platform']);
        Permission::firstOrCreate(['name' => 'structure system']);
        Permission::firstOrCreate(['name' => 'configure finances']);
        Permission::firstOrCreate(['name' => 'view global kpi']);

        // Gestionnaire permissions
        Permission::firstOrCreate(['name' => 'manage students']);
        Permission::firstOrCreate(['name' => 'manage coaches']);
        Permission::firstOrCreate(['name' => 'manage schedule']);
        Permission::firstOrCreate(['name' => 'prepare payments']);

        // Formateur permissions
        Permission::firstOrCreate(['name' => 'report sessions']);
        Permission::firstOrCreate(['name' => 'evaluate students']);
        Permission::firstOrCreate(['name' => 'manage assignments']);

        // Apprenant permissions
        Permission::firstOrCreate(['name' => 'view courses']);
        Permission::firstOrCreate(['name' => 'submit assignments']);
        Permission::firstOrCreate(['name' => 'view progress']);

        // create roles and assign created permissions

        // Apprenant
        $role = Role::firstOrCreate(['name' => 'student']);
        $role->givePermissionTo(['view courses', 'submit assignments', 'view progress']);

        // Formateur
        $role = Role::firstOrCreate(['name' => 'coach']);
        $role->givePermissionTo(['report sessions', 'evaluate students', 'manage assignments']);

        // Gestionnaire
        $role = Role::firstOrCreate(['name' => 'manager']);
        $role->givePermissionTo(['manage students', 'manage coaches', 'manage schedule', 'prepare payments']);

        // Admin
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo(Permission::all());
    }
}
