<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Filament\Resources\ShipmentResource;
use App\Support\CompanyProfile;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Url;

class PrintAirwayBill extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ShipmentResource::class;

    protected static ?string $title = 'Airway Bill';

    protected static ?string $breadcrumb = 'Airway Bill';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.shipments.print-airway-bill';

    #[Url(as: 'company')]
    public string $company = CompanyProfile::ATL;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_if($this->record->awb_number === null, 404, 'Nomor AWB belum dibuat untuk pengiriman ini.');

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
     * Judul dokumen mengikuti nomor AWB supaya nama file hasil "Save as PDF"
     * dari dialog cetak browser otomatis sama dengan nomor AWB.
     */
    public function getTitle(): string
    {
        return (string) $this->record->awb_number;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'shipment' => $this->record,
            'logs' => $this->record->logs()->latest('timestamp')->get(),
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
