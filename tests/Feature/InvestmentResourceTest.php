<?php

namespace Tests\Feature;

use App\Filament\Resources\InvestmentResource;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvestmentResourceTest extends TestCase
{
    use RefreshDatabase;

    private function createShipmentWithModal(float $modalAmount = 5000000): Shipment
    {
        $shipment = Shipment::create([
            'awb_number' => 'AWB-TEST-INV-1',
            'sender_name' => 'PT Pengirim',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'final_tariff' => 7500000,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ]);

        // Berikan invoice dengan real expense
        Invoice::create([
            'invoice_number' => 'INV-TEST-INV-1',
            'shipment_id' => $shipment->id,
            'shipping_cost' => 7500000,
            'shipping_real_expense' => $modalAmount,
            'subtotal' => 7500000,
            'discount' => 0,
            'tax' => 0,
            'total' => 7500000,
            'status' => Invoice::STATUS_FINAL,
        ]);

        return $shipment;
    }

    public function test_admin_can_access_investment_index(): void
    {
        $admin = User::factory()->create();
        $investor = Investor::create(['name' => 'Investor Utama', 'phone' => '081234']);

        Investment::create([
            'investor_id' => $investor->id,
            'project_name' => 'Proyek Pengiriman Genset',
            'amount' => 10000000,
            'status' => Investment::STATUS_ONGOING,
            'investment_date' => now()->toDateString(),
            'profit_sharing_percentage' => 15,
            'profit_sharing_amount' => 1500000,
            'profit_payout_status' => Investment::PAYOUT_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(InvestmentResource::getUrl('index'))
            ->assertSuccessful()
            ->assertSee('Investor Utama')
            ->assertSee('Proyek Pengiriman Genset')
            ->assertSee('10.000.000');
    }

    public function test_tracker_cannot_access_investment_index(): void
    {
        $tracker = User::factory()->tracker()->create();

        $this->actingAs($tracker)
            ->get(InvestmentResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_investment_and_auto_calculate_profit_sharing(): void
    {
        $admin = User::factory()->create();
        $investor = Investor::create(['name' => 'Investor Surya', 'phone' => '08129999']);
        $shipment = $this->createShipmentWithModal(4000000);

        Livewire::actingAs($admin)
            ->test(InvestmentResource\Pages\CreateInvestment::class)
            ->set('data.investor_id', $investor->id)
            ->set('data.shipment_id', $shipment->id)
            // Assert that setting shipment_id auto-filled amount with modal
            ->assertSet('data.amount', 4000000)
            // Set persentase bagi hasil
            ->set('data.profit_sharing_percentage', 20)
            ->assertSet('data.profit_sharing_amount', 800000)
            ->fillForm([
                'status' => Investment::STATUS_ONGOING,
                'investment_date' => '2026-11-10',
                'capital_payout_status' => Investment::PAYOUT_PENDING,
                'profit_payout_status' => Investment::PAYOUT_PENDING,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('investments', [
            'investor_id' => $investor->id,
            'shipment_id' => $shipment->id,
            'amount' => 4000000,
            'profit_sharing_percentage' => 20,
            'profit_sharing_amount' => 800000,
            'status' => 'ongoing',
        ]);
    }

    public function test_quick_payout_actions(): void
    {
        $admin = User::factory()->create();
        $investor = Investor::create(['name' => 'Investor Payout']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'project_name' => 'Project A',
            'amount' => 5000000,
            'status' => Investment::STATUS_ONGOING,
            'investment_date' => now()->toDateString(),
            'profit_sharing_percentage' => 10,
            'profit_sharing_amount' => 500000,
            'profit_payout_status' => Investment::PAYOUT_PENDING,
            'capital_payout_status' => Investment::PAYOUT_PENDING,
        ]);

        Livewire::actingAs($admin)
            ->test(InvestmentResource\Pages\ListInvestments::class)
            ->callTableAction('mark_profit_paid', $investment);

        $investment->refresh();
        $this->assertSame(Investment::PAYOUT_PAID, $investment->profit_payout_status);
        $this->assertNotNull($investment->profit_payout_date);

        Livewire::actingAs($admin)
            ->test(InvestmentResource\Pages\ListInvestments::class)
            ->callTableAction('mark_capital_reimbursed', $investment);

        $investment->refresh();
        $this->assertSame(Investment::PAYOUT_PAID, $investment->capital_payout_status);
        $this->assertSame(Investment::STATUS_REIMBURSED, $investment->status);
    }
}
