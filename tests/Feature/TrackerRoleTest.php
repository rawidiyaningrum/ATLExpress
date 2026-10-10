<?php

namespace Tests\Feature;

use App\Filament\Pages\FinanceDashboard;
use App\Filament\Resources\ShipmentResource;
use App\Filament\Resources\ShipmentTrackingResource;
use App\Filament\Resources\TrackerResource;
use App\Filament\Resources\TrackerResource\Pages\CreateTracker;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrackerRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_tracker_user(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateTracker::class)
            ->fillForm([
                'name' => 'Tracker Satu',
                'email' => 'tracker@example.com',
                'password' => 'secret-password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $tracker = User::where('email', 'tracker@example.com')->sole();

        $this->assertSame(User::ROLE_TRACKER, $tracker->role);
    }

    public function test_tracker_cannot_access_admin_only_menus(): void
    {
        $tracker = User::factory()->tracker()->create();

        $this->actingAs($tracker)->get(UserResource::getUrl('index'))->assertForbidden();
        $this->actingAs($tracker)->get(TrackerResource::getUrl('index'))->assertForbidden();
        $this->actingAs($tracker)->get(FinanceDashboard::getUrl())->assertForbidden();
    }

    public function test_tracker_can_view_shipments_but_not_create_them(): void
    {
        $tracker = User::factory()->tracker()->create();

        $this->actingAs($tracker)->get(ShipmentResource::getUrl('index'))->assertSuccessful();
        $this->actingAs($tracker)->get(ShipmentResource::getUrl('create'))->assertForbidden();
    }

    public function test_tracker_can_access_the_tracking_menu(): void
    {
        $tracker = User::factory()->tracker()->create();

        $this->actingAs($tracker)->get(ShipmentTrackingResource::getUrl('index'))->assertSuccessful();
        $this->actingAs($tracker)->get(ShipmentTrackingResource::getUrl('create'))->assertSuccessful();
    }

    public function test_tracker_resource_only_lists_trackers(): void
    {
        User::factory()->create(['name' => 'Admin Utama']);
        User::factory()->tracker()->create(['name' => 'Tracker Utama']);

        Livewire::actingAs(User::factory()->create())
            ->test(TrackerResource\Pages\ListTrackers::class)
            ->assertCanSeeTableRecords(User::trackers()->get())
            ->assertCanNotSeeTableRecords(User::where('role', User::ROLE_ADMIN)->get());
    }
}
