<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Filament\User\Pages\UserProfilePage;
use App\Models\User;
use App\Support\UserRole;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class UserProfilePageTest extends TestCase
{
    use RefreshDatabase;

    private function participantUser(array $overrides = []): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create($overrides);
        $user->assignRole(UserRole::User);

        return $user;
    }

    public function test_participant_can_view_profile_page(): void
    {
        $user = $this->participantUser([
            'phone' => '+36201110001',
            'bank_account' => '117730909900000011110001',
        ]);

        $this->actingAs($user)
            ->get(UserProfilePage::getUrl(panel: 'account'))
            ->assertOk();
    }

    public function test_save_rejects_duplicate_phone_from_another_user(): void
    {
        $this->participantUser([
            'phone' => '+36201110001',
            'bank_account' => '117730909900000011110001',
        ]);

        $other = $this->participantUser([
            'phone' => '+36202220002',
            'bank_account' => '117730909900000022220002',
        ]);

        Livewire::actingAs($other)
            ->test(UserProfilePage::class)
            ->set('data.name', 'Other User')
            ->set('data.phone', '+36201110001')
            ->set('data.bank_account', '117730909900000022220002')
            ->call('save')
            ->assertHasErrors(['data.phone']);
    }

    public function test_save_rejects_duplicate_bank_account_from_another_user(): void
    {
        $this->participantUser([
            'phone' => '+36201110001',
            'bank_account' => '117730909900000011110001',
        ]);

        $other = $this->participantUser([
            'phone' => '+36202220002',
            'bank_account' => '117730909900000022220002',
        ]);

        Livewire::actingAs($other)
            ->test(UserProfilePage::class)
            ->set('data.name', 'Other User')
            ->set('data.phone', '+36202220002')
            ->set('data.bank_account', '117730909900000011110001')
            ->call('save')
            ->assertHasErrors(['data.bank_account']);
    }

    public function test_user_can_update_own_profile(): void
    {
        $user = $this->participantUser([
            'name' => 'Before',
            'phone' => '+36203330333',
            'bank_account' => '117730909900000033330003',
        ]);

        Livewire::actingAs($user)
            ->test(UserProfilePage::class)
            ->set('data.name', 'After Name')
            ->set('data.phone', '+36300705352')
            ->set('data.bank_account', '117730909900000044440004')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('After Name', $user->name);
        $this->assertSame('+36300705352', $user->phone);
        $this->assertSame('117730909900000044440004', $user->bank_account);
    }
}
