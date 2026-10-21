<?php
namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Setting;
use App\Support\NumberToWordsId;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class PrintReceipt extends Page
{
    use InteractsWithRecord;

    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = "Kwitansi";

    protected static ?string $breadcrumb = "Kwitansi";

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = "filament.shipments.print-receipt";

    public function mount($record): void
    {
        abort_unless(auth()->user()?->isAdmin() ?? false, 403);

        $this->record = $this->resolveRecord($record);

        $this->record->loadMissing(array("shipment", "items"));
    }

    public function getTitle(): string
    {
        return "KWITANSI - " . ($this->record->invoice_number !== null && $this->record->invoice_number !== "" ? str_replace("INV", "KWI", $this->record->invoice_number) : "");
    }

    protected function getViewData(): array
    {
        $invoice = $this->record;
        $shipment = $invoice->shipment;

        $amount = (float) ($invoice->total ?? 0);
        $amountFormatted = number_format($amount, 0, ",", ".");

        $terbilang = trim((new NumberToWordsId())->convert($amount));

        $paymentFor = "" . ($invoice->invoice_number ? "Invoice " . $invoice->invoice_number : "");
        if ($shipment) {
            $paymentFor .= " | Pengiriman " . ($shipment->awb_number ?: "") . " | Rute: " . ($shipment->origin ?: "-") . " -> " . ($shipment->destination ?: "-");
            if ($shipment->item_type) {
                $paymentFor .= " | " . $shipment->item_type;
            }
        }

        return array(
            "invoice" => $invoice,
            "shipment" => $shipment,
            "kwitansi_no" => $invoice->invoice_number !== null && $invoice->invoice_number !== "" ? str_replace("INV", "KWI", $invoice->invoice_number) : "",
            "client_name" => $invoice->billed_to_name ?: ($shipment->sender_name ?? "-"),
            "amount_words" => "# " . $terbilang . " rupiah #",
            "payment_for" => trim($paymentFor),
            "receiver_name" => $shipment->receiver_name ?? "-",
            "amount_formatted" => $amountFormatted,
            "city_date" => "Jakarta, " . now()->translatedFormat("d F Y"),
            "signer_name" => Setting::getValue("receipt_signer_name") ?: "Harimsa S.H",
        );
    }

    protected function getHeaderActions(): array
    {
        return array(
            Action::make("print")
                ->label("Cetak")
                ->icon("heroicon-o-printer")
                ->alpineClickHandler("window.print()"),
        );
    }
}