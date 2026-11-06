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
        $this->call(CatalogSeeder::class);

        if (app()->isLocal()) {
            $admin = User::factory()->create([
                'name' => 'Admin Ehrungsfinder',
                'email' => 'admin@example.com',
            ]);
            $admin->forceFill(['is_admin' => true])->save();
        }
    }
}
