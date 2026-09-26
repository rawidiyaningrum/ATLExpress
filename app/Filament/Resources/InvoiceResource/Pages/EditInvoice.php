<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceService;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        abort_if(
            $this->record instanceof Invoice && $this->record->isLocked(),
            403,
            sprintf(
                'Invoice yang sudah berstatus %s tidak dapat diubah.',
                $this->record->statusLabel(),
            ),
        );
    }

    /**
     * Item hasil bersih dari form, dipakai ulang di afterSave().
     *
     * @var array<int, array<string, mixed>>
     */
    private array $items = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['invoice_items'] = $this->record->items()
            ->get()
            ->map(fn (InvoiceItem $item): array => [
                'description' => $item->description,
                'type' => $item->type,
                'basis' => $item->basis,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])
            ->values()
            ->all();

        return $data;
    }

    /**
     * Menyelaraskan ulang nominal invoice dari item yang diisikan.
     *
     * Repeater sengaja tidak memakai ->relationship(): Filament menyimpan
     * relasi dari state form apa adanya, sehingga basis dan line_total hasil
     * InvoiceService::normaliseItems() akan ditimpa nilai kosong.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $service = app(InvoiceService::class);
        $this->items = $service->normaliseItems((array) ($data['invoice_items'] ?? []));
        $totals = $service->calculate((float) ($data['shipping_cost'] ?? 0), $this->items);

        unset($data['invoice_items']);

        $data['shipping_cost'] = $totals['shipping'];
        $data['subtotal'] = $totals['subtotal'];
        $data['discount'] = $totals['discount'];
        $data['tax'] = $totals['tax'];
        $data['total'] = $totals['total'];

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->items()->delete();
        $this->record->items()->createMany($this->items);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
