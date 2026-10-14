<?php

namespace App\Filament\Resources\ShippingEstimateResource\Pages;

use App\Filament\Resources\ShippingEstimateResource;
use App\Models\ShippingEstimate;
use App\Services\ShippingEstimateService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateShippingEstimate extends CreateRecord
{
    protected static string $resource = ShippingEstimateResource::class;

    protected static ?string $title = 'Buat Estimasi Pengiriman';

    /**
     * Penyimpanan diserahkan ke service supaya nomor estimasi, baris armada,
     * dan biaya lain dibuat dalam satu transaksi.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(ShippingEstimateService::class)->make($data);
    }

    protected function getRedirectUrl(): string
    {
        $record = $this->record;

        if ($record instanceof ShippingEstimate) {
            return static::getResource()::getUrl('view', ['record' => $record]);
        }

        return parent::getRedirectUrl();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Estimasi pengiriman berhasil dibuat';
    }
}
