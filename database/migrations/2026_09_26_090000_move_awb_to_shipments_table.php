<?php

use App\Support\NumberGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memindahkan AWB ke satu sumber kebenaran: shipments.awb_number.
 *
 * Sebelumnya ShippingRequestResource::transferToShipment() menulis AWB ke
 * shipping_requests lalu membuat Shipment tanpa awb_number, sehingga
 * shipping_requests.awb_column adalah satu-satunya pemegang AWB yang riil.
 * Kolom itu dipindahkan ke shipments, lalu di-drop.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->backfillShipments();

        Schema::table('shipping_requests', function (Blueprint $table) {
            $table->dropUnique(['awb_number']);
            $table->dropColumn('awb_number');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_requests', function (Blueprint $table) {
            $table->string('awb_number')->nullable()->unique()->after('final_tariff');
        });

        // Memulihkan nilai lama tidak mungkin karena format berubah, jadi AWB
        // shipment disalin kembali dengan format yang sama seperti kondisi awal:
        // ada di kedua tabel.
        DB::table('shipments')
            ->whereNotNull('awb_number')
            ->whereNotNull('shipping_request_id')
            ->orderBy('id')
            ->select('id', 'shipping_request_id', 'awb_number')
            ->each(function ($shipment): void {
                DB::table('shipping_requests')
                    ->where('id', $shipment->shipping_request_id)
                    ->whereNull('awb_number')
                    ->update(['awb_number' => $shipment->awb_number]);
            });
    }

    private function backfillShipments(): void
    {
        $targets = DB::table('shipments')
            ->join('shipping_requests', 'shipments.shipping_request_id', '=', 'shipping_requests.id')
            ->whereNotNull('shipping_requests.awb_number')
            ->whereNull('shipments.awb_number')
            ->orderBy('shipping_requests.created_at')
            ->orderBy('shipments.id')
            ->get(['shipments.id', 'shipping_requests.created_at']);

        if ($targets->isEmpty()) {
            return;
        }

        $generator = app(NumberGenerator::class);

        foreach ($targets as $target) {
            // Tanggal mengikuti created_at pemesanan, bukan tanggal migrasi, supaya
            // urutan harian tidak bertumpuk pada satu tanggal. Generator membaca
            // shipments.awb_number, jadi setiap penulisan harus langsung disimpan.
            $awb = $generator->awbNumber($target->created_at);

            DB::table('shipments')->where('id', $target->id)->update(['awb_number' => $awb]);
        }
    }
};
