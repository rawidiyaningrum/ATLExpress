<?php

namespace Tests\Feature;

use App\Filament\Resources\InvestorResource;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvestorResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_investor_index(): void
    {
        $admin = User::factory()->create();

        Investor::create([
            'name' => 'Budi Hartono',
            'relation' => 'Rekan Bisnis',
            'phone' => '08123456789',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(InvestorResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Budi Hartono')
            ->assertSee('Rekan Bisnis');
    }

    public function test_tracker_cannot_access_investor_index(): void
    {
        $tracker = User::factory()->tracker()->create();

        $this->actingAs($tracker)
            ->get(InvestorResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_investor(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(InvestorResource\Pages\CreateInvestor::class)
            ->fillForm([
                'name' => 'Hendra Wijaya',
                'relation' => 'Keluarga',
                'phone' => '08198765432',
                'email' => 'hendra@example.com',
                'address' => 'Jl. Sudirman No. 45, Jakarta',
                'bank_name' => 'Mandiri',
                'bank_account_number' => '9876543210',
                'bank_account_name' => 'Hendra Wijaya',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('investors', [
            'name' => 'Hendra Wijaya',
            'relation' => 'Keluarga',
            'bank_name' => 'Mandiri',
        ]);
    }

    public function test_admin_can_edit_investor(): void
    {
        $admin = User::factory()->create();

        $investor = Investor::create([
            'name' => 'Agus Salim',
            'relation' => 'Sahabat',
            'phone' => '08111222333',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(InvestorResource\Pages\EditInvestor::class, [
                'record' => $investor->getRouteKey(),
            ])
            ->fillForm([
                'name' => 'Agus Salim Updated',
                'relation' => 'Partner Strategis',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('investors', [
            'id' => $investor->id,
            'name' => 'Agus Salim Updated',
            'relation' => 'Partner Strategis',
        ]);
    }
}
