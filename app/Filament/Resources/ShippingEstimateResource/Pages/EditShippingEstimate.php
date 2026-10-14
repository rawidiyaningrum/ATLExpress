<?php

namespace App\Filament\Resources\ShippingEstimateResource\Pages;

use App\Filament\Resources\ShippingEstimateResource;
use App\Models\ShippingEstimate;
use App\Models\ShippingEstimateArmada;
use App\Models\ShippingEstimateCharge;
use App\Services\ShippingEstimateService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditShippingEstimate extends EditRecord
{
    protected static string $resource = ShippingEstimateResource::class;

    protected static ?string $title = 'Ubah Estimasi Pengiriman';

    /**
     * Repeater armada dan biaya lain diisi dari relasi, karena datanya tidak
     * ikut pada atribut record sehingga form ubah tampil kosong tanpa ini.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var ShippingEstimate $record */
        $record = $this->record;

        $data['armadas'] = $record->armadas
            ->map(fn (ShippingEstimateArmada $armada): array => [
                'service_type' => $armada->service_type,
                'price_per_kg' => (float) $armada->price_per_kg,
                'final_tariff' => $armada->final_tariff !== null ? (float) $armada->final_tariff : null,
                'min_weight' => (float) $armada->min_weight,
                'estimated_days' => $armada->estimated_days,
            ])
            ->all();

        $data['charges'] = $record->charges
            ->map(fn (ShippingEstimateCharge $charge): array => [
                'description' => $charge->description,
                'type' => $charge->type,
                'quantity' => (int) $charge->quantity,
                'unit_price' => (float) $charge->unit_price,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ShippingEstimate $record */
        return app(ShippingEstimateService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cetak')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => PrintShippingEstimate::getUrl(['record' => $this->record]))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make()
                ->label('Hapus'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        $record = $this->record;

        if ($record instanceof ShippingEstimate) {
            return static::getResource()::getUrl('view', ['record' => $record]);
        }

        return parent::getRedirectUrl();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Estimasi pengiriman berhasil diperbarui';
    }
}
