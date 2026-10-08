<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class PrintInvoice extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ShipmentResource::class;

    protected static ?string $title = 'Invoice';

    protected static ?string $breadcrumb = 'Invoice';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.shipments.print-invoice';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_if(
            $this->record->latestInvoice === null,
            404,
            'Invoice belum dibuat untuk pengiriman ini.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'shipment' => $this->record,
            'invoice' => $this->record->latestInvoice,
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
