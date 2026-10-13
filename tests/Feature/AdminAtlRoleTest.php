<?php

namespace Tests\Feature;

use App\Filament\Pages\FinanceDashboard;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\FinanceJournalResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\ServiceResource;
use App\Filament\Resources\SettingResource;
use App\Filament\Resources\ShipmentResource;
use App\Filament\Resources\ShipmentResource\Pages\CreateShipment;
use App\Filament\Resources\ShippingRateResource;
use App\Filament\Resources\ShippingRequestResource;
use App\Filament\Resources\TrackerResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAtlRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_atl_can_access_general_admin_menus(): void
    {
        $adminAtl = User::factory()->adminAtl()->create();

        $this->actingAs($adminAtl)->get(ShipmentResource::getUrl('index'))->assertSuccessful();
        $this->actingAs($adminAtl)->get(ShipmentResource::getUrl('create'))->assertSuccessful();
        $this->actingAs($adminAtl)->get(ShippingRateResource::getUrl('index'))->assertSuccessful();
        $this->actingAs($adminAtl)->get(ShippingRequestResource::getUrl('index'))->assertSuccessful();
        $this->actingAs($adminAtl)->get(CategoryResource::getUrl('index'))->assertSuccessful();
        $this->actingAs($adminAtl)->get(ServiceResource::getUrl('index'))->assertSuccessful();
        $this->actingAs($adminAtl)->get(SettingResource::getUrl('index'))->assertSuccessful();
        $this->actingAs($adminAtl)->get(TrackerResource::getUrl('index'))->assertSuccessful();
    }

    public function test_admin_atl_cannot_access_invoice_and_finance_menus(): void
    {
        $adminAtl = User::factory()->adminAtl()->create();

        $this->actingAs($adminAtl)->get(InvoiceResource::getUrl('index'))->assertForbidden();
        $this->actingAs($adminAtl)->get(FinanceJournalResource::getUrl('index'))->assertForbidden();
        $this->actingAs($adminAtl)->get(FinanceDashboard::getUrl())->assertForbidden();
    }

    public function test_admin_atl_cannot_manage_admin_accounts(): void
    {
        $adminAtl = User::factory()->adminAtl()->create();

        $this->actingAs($adminAtl)->get(UserResource::getUrl('index'))->assertForbidden();
    }

    public function test_full_admin_can_create_an_admin_account_from_the_user_menu(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertActionVisible('create');

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Admin ATL Baru',
                'email' => 'atl-baru@example.com',
                'password' => 'secret-password',
                'role' => User::ROLE_ADMIN_ATL,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'atl-baru@example.com',
            'role' => User::ROLE_ADMIN_ATL,
        ]);
    }

    public function test_shipment_wizard_hides_invoice_steps_for_admin_atl(): void
    {
        $adminAtl = User::factory()->adminAtl()->create();
        $admin = User::factory()->create();

        $adminAtlSteps = Livewire::actingAs($adminAtl)->test(CreateShipment::class)->instance()->getSteps();
        $adminSteps = Livewire::actingAs($admin)->test(CreateShipment::class)->instance()->getSteps();

        $this->assertCount(3, $adminAtlSteps);
        $this->assertCount(5, $adminSteps);
    }
}
