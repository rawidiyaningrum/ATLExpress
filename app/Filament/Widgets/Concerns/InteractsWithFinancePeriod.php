<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\FinanceJournal;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

trait InteractsWithFinancePeriod
{
    public ?string $financeFrom = null;

    public ?string $financeTo = null;

    /**
     * Menerima periode dari halaman dashboard finance.
     *
     * Widget finance tidak dideteksi otomatis oleh panel, jadi satu-satunya
     * jalan masuknya periode adalah event ini.
     */
    #[On('finance-period-changed')]
    public function applyFinancePeriod(?string $from = null, ?string $to = null): void
    {
        $this->financeFrom = $from;
        $this->financeTo = $to;

        $this->flushFinanceCache();
    }

    /**
     * Rentang tanggal yang dipakai widget.
     *
     * Tanpa event, widget jatuh ke bulan berjalan supaya halaman yang dibuka
     * langsung lewat URL dengan periode lain tidak menampilkan angka kosong.
     *
     * @return array{0: string, 1: string}
     */
    protected function financePeriod(): array
    {
        $from = $this->financeFrom !== null
            ? Carbon::parse($this->financeFrom)->startOfDay()
            : now()->startOfMonth();

        $to = $this->financeTo !== null
            ? Carbon::parse($this->financeTo)->endOfDay()
            : now()->endOfMonth();

        return [$from->toDateString(), $to->toDateString()];
    }

    protected function financePeriodLabel(): string
    {
        [$from, $to] = $this->financePeriod();

        $start = Carbon::parse($from);
        $end = Carbon::parse($to);

        return $start->isSameMonth($end)
            ? $start->translatedFormat('d M Y')
            : "{$start->translatedFormat('d M Y')} - {$end->translatedFormat('d M Y')}";
    }

    /**
     * Jurnal pendapatan di periode berjalan.
     *
     * Jurnal kas masuk sengaja dikecualikan. Satu invoice punya dua jurnal:
     * jurnal pendapatan saat ditagih dan jurnal kas saat dilunasi. Kalau keduanya
     * dijumlahkan, pendapatan dan profit akan terhitung dua kali.
     */
    protected function financeRevenueQuery(): Builder
    {
        [$from, $to] = $this->financePeriod();

        return FinanceJournal::query()
            ->where('journal_type', FinanceJournal::TYPE_REVENUE)
            ->whereBetween('entry_date', [$from, $to]);
    }

    /**
     * Jurnal kas masuk di periode berjalan.
     */
    protected function financeReceiptQuery(): Builder
    {
        [$from, $to] = $this->financePeriod();

        return FinanceJournal::query()
            ->where('journal_type', FinanceJournal::TYPE_RECEIPT)
            ->whereBetween('entry_date', [$from, $to]);
    }

    /**
     * Jurnal apa pun di periode berjalan, untuk tabel jurnal terbaru.
     */
    protected function financeQuery(): Builder
    {
        [$from, $to] = $this->financePeriod();

        return FinanceJournal::query()->whereBetween('entry_date', [$from, $to]);
    }

    /**
     * Agregasi satu kali untuk seluruh nominal di periode berjalan.
     *
     * real_expense adalah alias dari cost_of_goods supaya widget bisa menyebutnya
     * dengan nama yang tepat: untuk jurnal dari invoice, kolom modal tersebut
     * berisi jumlah pengeluaran real per item yang dicatat operator.
     *
     * @return array{entries: int, income: float, cost_of_goods: float, real_expense: float, operational_cost: float, tax: float, total_expense: float, profit: float, average_percentage: float}
     */
    protected function financeTotals(): array
    {
        $totals = $this->financeRevenueQuery()
            ->selectRaw('count(*) as entries')
            ->selectRaw('coalesce(sum(income), 0) as income')
            ->selectRaw('coalesce(sum(cost_of_goods), 0) as cost_of_goods')
            ->selectRaw('coalesce(sum(cost_of_goods), 0) as real_expense')
            ->selectRaw('coalesce(sum(operational_cost), 0) as operational_cost')
            ->selectRaw('coalesce(sum(tax), 0) as tax')
            ->selectRaw('coalesce(sum(total_expense), 0) as total_expense')
            ->selectRaw('coalesce(sum(profit), 0) as profit')
            ->selectRaw('coalesce(avg(profit_percentage), 0) as average_percentage')
            ->first();

        return [
            'entries' => (int) ($totals?->entries ?? 0),
            'income' => (float) ($totals?->income ?? 0),
            'cost_of_goods' => (float) ($totals?->cost_of_goods ?? 0),
            'real_expense' => (float) ($totals?->real_expense ?? 0),
            'operational_cost' => (float) ($totals?->operational_cost ?? 0),
            'tax' => (float) ($totals?->tax ?? 0),
            'total_expense' => (float) ($totals?->total_expense ?? 0),
            'profit' => (float) ($totals?->profit ?? 0),
            'average_percentage' => (float) ($totals?->average_percentage ?? 0),
        ];
    }

    /**
     * Total kas masuk di periode berjalan.
     *
     * Yang dijumlahkan adalah nominal kas riil yang dicatat operator. Jurnal
     * yang nominalnya belum dicatat jatuh ke total tagihannya, supaya kartu ini
     * tidak ikut turun ke nol hanya karena satu jurnal belum sempat diisi.
     */
    protected function financeCashReceived(): float
    {
        return (float) ($this->financeReceiptQuery()
            ->selectRaw('coalesce(sum(coalesce(real_income, income)), 0) as income')
            ->value('income') ?? 0);
    }

    /**
     * Saldo piutang yang belum dibayar, yaitu invoice yang sudah ditagihkan tapi
     * belum lunas.
     *
     * Ini saldo berjalan, bukan aliran periode, jadi sengaja tidak ikut
     * difilter tanggal: tagihan tahun lalu yang masih mengejap tetap harus
     * terlihat saat dashboard difilter bulan ini.
     *
     * @return array{count: int, total: float}
     */
    protected function financeOutstandingReceivable(): array
    {
        $outstanding = Invoice::query()
            ->where('status', Invoice::STATUS_TERTAGIH)
            ->selectRaw('count(*) as entries')
            ->selectRaw('coalesce(sum(total), 0) as total')
            ->first();

        return [
            'count' => (int) ($outstanding?->entries ?? 0),
            'total' => (float) ($outstanding?->total ?? 0),
        ];
    }

    /**
     * Widget statistik dan grafik menyimpan hasil query-nya, jadi cache harus
     * dibuang setiap periode berubah.
     */
    protected function flushFinanceCache(): void
    {
        foreach (['cachedStats', 'cachedData'] as $property) {
            if (property_exists($this, $property)) {
                $this->{$property} = null;
            }
        }

        if (method_exists($this, 'resetTable')) {
            $this->resetTable();
        }
    }
}
