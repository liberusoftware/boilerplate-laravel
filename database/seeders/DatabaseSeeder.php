<?php

namespace Database\Seeders;

use App\Models\Module;
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
        $this->call([
            TeamSeeder::class,
            RolesSeeder::class,
            UserSeeder::class,
            GroupSeeder::class,
            PostSeeder::class,
        ]);

        // Blog is the reference App\Modules implementation — ship it enabled by default.
        Module::firstOrCreate(['name' => 'Blog'], [
            'enabled' => true,
            'version' => '1.0.0',
            'description' => 'A working blog module — the reference implementation for app/Modules.',
        ]);
    }
}
