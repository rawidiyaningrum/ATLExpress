<?php

namespace App\Filament\Resources\ShippingEstimateResource\Pages;

use App\Filament\Resources\ShippingEstimateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class PrintShippingEstimate extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ShippingEstimateResource::class;

    protected static ?string $title = 'Cetak Estimasi';

    protected static ?string $breadcrumb = 'Cetak';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.shipping-estimates.print';

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()?->isAdminRole() ?? false, 403);

        $this->record = $this->resolveRecord($record);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'estimate' => $this->record,
            'rows' => $this->record->comparison(),
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
