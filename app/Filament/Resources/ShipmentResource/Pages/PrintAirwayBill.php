<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class PrintAirwayBill extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ShipmentResource::class;

    protected static ?string $title = 'Airway Bill';

    protected static ?string $breadcrumb = 'Airway Bill';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.shipments.print-airway-bill';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_if($this->record->awb_number === null, 404, 'Nomor AWB belum dibuat untuk pengiriman ini.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'shipment' => $this->record,
            'logs' => $this->record->logs()->latest('timestamp')->get(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->alpineClickHandler('window.print()'),
        ];
    }
}
