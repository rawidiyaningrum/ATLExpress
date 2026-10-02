<?php

namespace Tests\Feature;

use App\Filament\Resources\FinanceJournalResource\Pages\ListFinanceJournals;
use App\Models\FinanceJournal;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceJournalMenuTest extends TestCase
{
    use RefreshDatabase;

    private function journal(string $type, float $income = 150000): FinanceJournal
    {
        $shipment = Shipment::create([
            'awb_number' => 'AWB-MENU-'.Shipment::count().'-'.FinanceJournal::count(),
            'sender_name' => 'PT Kirim Sejahtera',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'final_tariff' => $income,
            'status' => Shipment::STATUS_IN_TRANSIT,
        ]);

        return FinanceJournal::create([
            'shipment_id' => $shipment->id,
            'reference_label' => 'REF-'.FinanceJournal::count(),
            'journal_type' => $type,
            'entry_date' => now()->toDateString(),
            'income' => $income,
            'cost_of_goods' => 0,
            'operational_cost' => 0,
            'tax' => 0,
        ]);
    }

    public function test_journal_menu_exposes_a_journal_type_filter(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ListFinanceJournals::class)
            ->assertTableFilterExists('journal_type', function ($filter): bool {
                return $filter->getOptions() === FinanceJournal::TYPE_LABELS
                    && ! $filter->isMultiple();
            })
            ->assertTableFilterVisible('journal_type');
    }

    public function test_journal_menu_filters_by_journal_type(): void
    {
        $revenue = $this->journal(FinanceJournal::TYPE_REVENUE);
        $receipt = $this->journal(FinanceJournal::TYPE_RECEIPT);

        // Argumen kedua makro ini adalah flag $inOrder, bukan pesan kegagalan,
        // jadi assertion harus dibiarkan tanpa argumen tambahan.
        Livewire::actingAs(User::factory()->create())
            ->test(ListFinanceJournals::class)
            ->assertCanSeeTableRecords([$revenue, $receipt])
            ->filterTable('journal_type', FinanceJournal::TYPE_REVENUE)
            ->assertCanSeeTableRecords([$revenue])
            ->assertCanNotSeeTableRecords([$receipt]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListFinanceJournals::class)
            ->filterTable('journal_type', FinanceJournal::TYPE_RECEIPT)
            ->assertCanSeeTableRecords([$receipt])
            ->assertCanNotSeeTableRecords([$revenue]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListFinanceJournals::class)
            ->filterTable('journal_type', FinanceJournal::TYPE_RECEIPT)
            ->resetTableFilters()
            ->assertCanSeeTableRecords([$revenue, $receipt]);
    }

    public function test_journal_filter_trigger_is_a_labelled_button(): void
    {
        $component = Livewire::actingAs(User::factory()->create())
            ->test(ListFinanceJournals::class);

        $trigger = $component->instance()->getTable()->getFiltersTriggerAction();

        // Ikon corong tanpa teks sulit ditemukan, jadi tombolnya harus tetap
        // memakai view button yang menampilkan label.
        $this->assertTrue($trigger->isButton(), 'tombol filter harus berupa tombol berlabel, bukan icon button');
        $this->assertSame('Filter', $trigger->getLabel());
    }
}
