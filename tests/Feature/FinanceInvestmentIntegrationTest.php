<?php

namespace Tests\Feature;

use App\Filament\Widgets\FinanceOverviewStats;
use App\Models\FinanceJournal;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceInvestmentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function createShipmentAndJournal(float $income = 10000000, float $modal = 6000000): array
    {
        $shipment = Shipment::create([
            'awb_number' => 'AWB-INT-1',
            'sender_name' => 'PT Kargo Solusi',
            'origin' => 'Jakarta',
            'destination' => 'Balikpapan',
            'final_tariff' => $income,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-INT-1',
            'shipment_id' => $shipment->id,
            'shipping_cost' => $income,
            'shipping_real_expense' => $modal,
            'subtotal' => $income,
            'discount' => 0,
            'tax' => 0,
            'total' => $income,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        app(InvoiceService::class)->markFinal($invoice);

        $journal = FinanceJournal::query()
            ->where('shipment_id', $shipment->id)
            ->where('journal_type', FinanceJournal::TYPE_REVENUE)
            ->firstOrFail();

        return [$shipment, $journal, $invoice];
    }

    public function test_investment_profit_sharing_reduces_journal_profit(): void
    {
        // Pendapatan 10.000.000, modal 6.000.000 -> profit awal = 4.000.000
        [$shipment, $journal] = $this->createShipmentAndJournal(10000000, 6000000);

        $this->assertEquals(4000000.0, (float) $journal->profit);
        $this->assertEquals(0.0, (float) $journal->investment_profit_sharing);

        // Buat investor & investment dengan bagi hasil 1.000.000 (modal 5.000.000, 20%)
        $investor = Investor::create(['name' => 'Investor Roy']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'shipment_id' => $shipment->id,
            'amount' => 5000000,
            'status' => Investment::STATUS_ONGOING,
            'investment_date' => now()->toDateString(),
            'profit_sharing_percentage' => 20,
            'profit_sharing_amount' => 1000000,
            'profit_payout_status' => Investment::PAYOUT_PENDING,
        ]);

        // Jurnal otomatis tersinkronisasi
        $journal->refresh();

        $this->assertEquals(1000000.0, (float) $journal->investment_profit_sharing, 'Bagi hasil tercatat di jurnal');
        // Profit baru: 10.000.000 - 6.000.000 - 1.000.000 = 3.000.000
        $this->assertEquals(3000000.0, (float) $journal->profit, 'Profit berkurang sebesar bagi hasil investasi');
        $this->assertEquals(30.0, (float) $journal->profit_percentage, 'Persentase profit menyesuaikan');
    }

    public function test_unpaid_profit_sharing_enters_liabilities_hutang(): void
    {
        $admin = User::factory()->create();
        [$shipment, $journal] = $this->createShipmentAndJournal(10000000, 6000000);
        $investor = Investor::create(['name' => 'Investor Rudy']);

        $investment = Investment::create([
            'investor_id' => $investor->id,
            'shipment_id' => $shipment->id,
            'amount' => 5000000,
            'status' => Investment::STATUS_ONGOING,
            'investment_date' => now()->toDateString(),
            'profit_sharing_percentage' => 15,
            'profit_sharing_amount' => 750000,
            'profit_payout_status' => Investment::PAYOUT_PENDING,
            'capital_payout_status' => Investment::PAYOUT_PENDING,
        ]);

        // Dashboard finance widget
        $stats = Livewire::actingAs($admin)
            ->test(FinanceOverviewStats::class)
            ->assertSee('Hutang Bagi Hasil')
            ->assertSee('750.000')
            ->assertSee('Hutang Pokok Modal')
            ->assertSee('5.000.000')
            ->assertSee('Bagi Hasil Investor');

        // Saat bagi hasil dicairkan
        $investment->update([
            'profit_payout_status' => Investment::PAYOUT_PAID,
            'profit_payout_date' => now()->toDateString(),
        ]);

        // Jurnal tetap terpotong
        $journal->refresh();
        $this->assertEquals(750000.0, (float) $journal->investment_profit_sharing);
        $this->assertEquals(3250000.0, (float) $journal->profit);

        // Hutang bagi hasil di dashboard menjadi 0
        Livewire::actingAs($admin)
            ->test(FinanceOverviewStats::class)
            ->assertSee('Hutang Bagi Hasil')
            ->assertSee('Rp 0');
    }
}
