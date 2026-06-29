<?php

namespace Tests\Feature;

use App\Livewire\Settings\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_upload_and_remove_a_user_avatar(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $user = User::factory()->create(['role' => 'manager', 'is_active' => true]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('editUser', $user)
            ->set('userAvatar', UploadedFile::fake()->image('profil.png', 900, 600))
            ->call('saveUser')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('editUser', $user)
            ->set('removeUserAvatar', true)
            ->call('saveUser')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing($user->avatar_path);
        $this->assertNull($user->fresh()->avatar_path);
    }
}
