<?php

namespace Database\Seeders;

use App\Models\Post;
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
        $user1 = User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        Post::factory()->create([
            'user_id' => $user1->id,
        ]);

        $user2 = User::factory()->create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
        ]);

        Post::factory()->create([
            'user_id' => $user2->id,
        ]);
    }
}
