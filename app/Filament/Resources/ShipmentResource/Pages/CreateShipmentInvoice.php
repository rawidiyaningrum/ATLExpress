<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\ShipmentResource;
use App\Filament\Resources\ShipmentResource\Concerns\HasInvoiceForm;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

/**
 * Pembuatan invoice di luar wizard shipment.
 *
 * Dipakai dari tombol "Buat Invoice" pada daftar dan detail shipment yang
 * belum punya invoice, supaya invoice tetap bisa terbit tanpa mengulang wizard.
 */
class CreateShipmentInvoice extends Page
{
    use HasInvoiceForm;
    use InteractsWithRecord;

    protected static string $resource = ShipmentResource::class;

    protected static ?string $title = 'Buat Invoice';

    protected static ?string $breadcrumb = 'Buat Invoice';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.shipments.shipment-invoice';

    /**
     * State form. Harus dideklarasikan, sama seperti halaman form bawaan
     * Filament, karena Livewire memvalidasi rules terhadap properti ini.
     */
    public ?array $data = [];

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()?->isAdmin() ?? false, 403);

        $this->record = $this->resolveRecord($record);

        abort_if(
            $this->shipment()->latestInvoice !== null,
            404,
            'Invoice untuk pengiriman ini sudah ada.',
        );

        abort_if(
            $this->shipment()->status === 'cancelled',
            404,
            'Pengiriman batal tidak bisa dibuatkan invoice baru.',
        );

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->invoiceFormSchema())
            ->statePath('data');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveDraft')
                ->label('Simpan Draft')
                ->icon('heroicon-o-pencil')
                ->color('gray')
                ->action(fn () => $this->store(Invoice::STATUS_DRAFT)),
            Action::make('saveBilled')
                ->label('Tertagih & Cetak')
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->action(fn () => $this->store(Invoice::STATUS_TERTAGIH)),
        ];
    }

    /**
     * Menyimpan invoice dengan status yang dipilih, lalu mengarahkan ke
     * halaman yang relevan: detail invoice untuk draft, cetak untuk yang
     * sudah ditagihkan.
     */
    protected function store(string $status): void
    {
        $state = $this->form->getState();

        $invoice = app(InvoiceService::class)->saveForShipment(
            $this->shipment(),
            [
                ...$this->invoiceDefaultsFromShipment($state),
                'items' => $state['invoice_items'] ?? [],
            ],
            $status,
        );

        $this->redirect($status !== Invoice::STATUS_DRAFT
            ? static::getResource()::getUrl('print-invoice', ['record' => $this->record])
            : InvoiceResource::getUrl('view', ['record' => $invoice]));
    }

    private function shipment(): Shipment
    {
        abort_unless($this->record instanceof Shipment, 404);

        return $this->record;
    }
}
