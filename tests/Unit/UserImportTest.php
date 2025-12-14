<?php

namespace Tests\Unit;


use Tests\TestCase;
use App\Models\User;
use App\Models\ImportReport;
use App\Models\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserImportTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic unit test example.
     */
    public function test_new_user_is_created_with_image()
    {


        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret',
            'image_name' => 'photos/avatar.png',
        ];

        $this->runUpsertLogic($data);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $user = User::where('email', 'john@example.com')->first();

        $this->assertDatabaseHas('images', [
            'user_id' => $user->id,
            'original_name' => 'avatar.png',
        ]);
    }
    private function runUpsertLogic(array $data): void
    {
        $existing = User::where('email', $data['email'])->first();

        $imageName = $data['image_name'] !== ''
            ? basename($data['image_name'])
            : null;

        if ($existing) {
            $existing->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
            ]);

            if ($imageName) {
                Image::updateOrCreate(
                    ['user_id' => $existing->id],
                    [
                        'original_name' => $imageName,
                        'user_id' => $existing->id,
                    ]
                );
            }
        } else {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
            ]);

            if ($imageName) {
                Image::create([
                    'original_name' => $imageName,
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
