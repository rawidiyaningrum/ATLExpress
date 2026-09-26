<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FinanceBreakdownChart;
use App\Filament\Widgets\FinanceOverviewStats;
use App\Filament\Widgets\FinanceTrendChart;
use App\Filament\Widgets\RecentFinanceJournalsTable;
use DateTimeInterface;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

class FinanceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Dashboard Finance';

    protected static ?string $title = 'Dashboard Finance';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.finance-dashboard';

    /**
     * State form. Harus dideklarasikan sebagai properti supaya Livewire
     * meninggalkannya utuh saat render dan saat form diisi dari mount().
     *
     * @var array{period: string|null, from: string|null, to: string|null}
     */
    public array $filter = [
        'period' => 'this_month',
        'from' => null,
        'to' => null,
    ];

    /**
     * Periode disimpan di query string supaya tampilan yang sedang dibaca
     * bisa di-bookmark dan dibagikan.
     */
    #[Url(as: 'period', history: false)]
    public ?string $period = 'this_month';

    #[Url(as: 'from', history: false)]
    public ?string $from = null;

    #[Url(as: 'to', history: false)]
    public ?string $to = null;

    public function mount(): void
    {
        [$from, $to] = $this->resolvePeriod($this->period, $this->from, $this->to);

        $this->form->fill([
            'period' => $this->period,
            'from' => $from,
            'to' => $to,
        ]);

        $this->publishPeriod();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('period')
                            ->label('Periode')
                            ->options([
                                'today' => 'Hari Ini',
                                '7_days' => '7 Hari Terakhir',
                                'this_month' => 'Bulan Ini',
                                'last_month' => 'Bulan Lalu',
                                'this_year' => 'Tahun Ini',
                                'custom' => 'Rentang Kustom',
                            ])
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(fn (?string $state, Forms\Set $set) => $this->applyPreset($state, $set)),
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari')
                            ->live()
                            ->afterStateUpdated(fn (mixed $state, Forms\Set $set) => $this->markAsCustom($set)),
                        Forms\Components\DatePicker::make('to')
                            ->label('Sampai')
                            ->live()
                            ->afterStateUpdated(fn (mixed $state, Forms\Set $set) => $this->markAsCustom($set)),
                    ]),
            ])
            // Path state eksplisit: tanpa ini state form menumpang di root
            // komponen Livewire dan bentrok dengan properti URL.
            ->statePath('filter');
    }

    /**
     * Widget finance berada di footer supaya filter periode di atasnya dibaca
     * lebih dulu, dan tidak ikut muncul di dashboard pengiriman.
     *
     * @return array<class-string<Widget>>
     */
    protected function getFooterWidgets(): array
    {
        return [
            FinanceOverviewStats::class,
            FinanceTrendChart::class,
            FinanceBreakdownChart::class,
            RecentFinanceJournalsTable::class,
        ];
    }

    /**
     * Mengisi rentang tanggal dari preset pilihan lalu meneruskannya ke widget.
     */
    protected function applyPreset(?string $period, Forms\Set $set): void
    {
        $period ??= 'this_month';

        if ($period !== 'custom') {
            [$from, $to] = $this->resolvePeriod($period, null, null);

            $set('from', $from);
            $set('to', $to);
        }

        $this->publishPeriod();
    }

    /**
     * Tanggal yang diketik manual selalu berarti rentang kustom.
     */
    protected function markAsCustom(Forms\Set $set): void
    {
        $set('period', 'custom');

        $this->publishPeriod();
    }

    /**
     * Menyalin state form ke properti URL lalu memberi tahu widget finance.
     */
    protected function publishPeriod(): void
    {
        $state = $this->form->getState();

        $this->period = $state['period'] ?? 'this_month';
        $this->from = $this->normaliseDate($state['from'] ?? null);
        $this->to = $this->normaliseDate($state['to'] ?? null);

        $this->dispatch('finance-period-changed', from: $this->from, to: $this->to);
    }

    /**
     * Tanggal awal dan akhir dari sebuah preset.
     *
     * Rentang kustom memakai tanggal yang sudah ada, dan bila tanggal awal lebih
     * besar dari tanggal akhir keduanya ditukar supaya widget tidak mencari
     * periode yang kosong.
     *
     * @return array{0: string, 1: string}
     */
    protected function resolvePeriod(?string $period, ?string $from, ?string $to): array
    {
        $start = match ($period) {
            'today' => now(),
            '7_days' => now()->subDays(6),
            'last_month' => now()->subMonthNoOverflow()->startOfMonth(),
            'this_year' => now()->startOfYear(),
            'custom' => $from !== null ? Carbon::parse($from) : now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $end = match ($period) {
            'today' => now(),
            '7_days' => now(),
            'last_month' => now()->subMonthNoOverflow()->endOfMonth(),
            'this_year' => now()->endOfYear(),
            'custom' => $to !== null ? Carbon::parse($to) : now()->endOfMonth(),
            default => now()->endOfMonth(),
        };

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    protected function normaliseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value instanceof DateTimeInterface
            ? Carbon::instance($value)->toDateString()
            : Carbon::parse($value)->toDateString();
    }
}
