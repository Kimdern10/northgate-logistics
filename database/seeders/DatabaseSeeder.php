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
     *
     * On the live server this only adds the website content and rates; make the
     * owner's login there with `php artisan app:create-admin`.
     */
    public function run(): void
    {
        $this->call(ContentSeeder::class);

        if (app()->isProduction()) {
            return;
        }

        User::factory()->create([
            'name' => 'Northgate Admin',
            'email' => 'admin@northgate.example',
            'is_admin' => true,
        ]);

        $this->call(DemoShipmentSeeder::class);
    }
}
