<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Support\CompanyProfile;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Url;

class PrintInvoice extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ShipmentResource::class;

    protected static ?string $title = 'Invoice';

    protected static ?string $breadcrumb = 'Invoice';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.shipments.print-invoice';

    #[Url(as: 'company')]
    public string $company = CompanyProfile::ATL;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()?->isAdmin() ?? false, 403);

        $this->record = $this->resolveRecord($record);

        abort_if(
            $this->record->latestInvoice === null,
            404,
            'Invoice belum dibuat untuk pengiriman ini.',
        );

        if (! in_array($this->company, [CompanyProfile::ATL, CompanyProfile::DOUZE], true)) {
            $this->company = CompanyProfile::ATL;
        }
    }

    public function setCompany(string $company): void
    {
        if (in_array($company, [CompanyProfile::ATL, CompanyProfile::DOUZE], true)) {
            $this->company = $company;
        }
    }

    /**
     * Judul dokumen mengikuti nomor invoice supaya nama file hasil "Save as PDF"
     * dari dialog cetak browser otomatis sama dengan nomor invoice.
     */
    public function getTitle(): string
    {
        return (string) $this->record->latestInvoice->invoice_number;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'shipment' => $this->record,
            'invoice' => $this->record->latestInvoice,
            'company' => $this->company,
            'companyProfile' => CompanyProfile::get($this->company),
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
