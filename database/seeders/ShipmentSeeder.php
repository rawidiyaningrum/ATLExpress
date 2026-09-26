<?php

namespace Database\Seeders;

use App\Models\Shipment;
use App\Models\ShipmentLog;
use Illuminate\Database\Seeder;

class ShipmentSeeder extends Seeder
{
    public function run(): void
    {
        $shipments = [
            [
                'awb_number' => 'AWB_ATL_20250101001',
                'sender_name' => 'PT Sinar Maju',
                'receiver_name' => 'CV Karya Abadi',
                'origin' => 'Jakarta',
                'destination' => 'Surabaya',
                'service_type' => 'darat',
                'weight' => 15.5,
                'dimension_length' => 50,
                'dimension_width' => 40,
                'dimension_height' => 30,
                'status' => 'delivered',
                'logs' => [
                    ['status_description' => 'Barang diterima di kantor cabang Jakarta', 'location' => 'Jakarta', 'timestamp' => now()->subDays(3)],
                    ['status_description' => 'Barang sedang dalam perjalanan menuju Surabaya', 'location' => 'Cikampek', 'timestamp' => now()->subDays(2)],
                    ['status_description' => 'Barang telah diterima oleh penerima', 'location' => 'Surabaya', 'timestamp' => now()->subDay()],
                ],
            ],
            [
                'awb_number' => 'AWB_ATL_20250102001',
                'sender_name' => 'Budi Santoso',
                'receiver_name' => 'Andi Pratama',
                'origin' => 'Jakarta',
                'destination' => 'Medan',
                'service_type' => 'udara',
                'weight' => 8.0,
                'dimension_length' => 30,
                'dimension_width' => 25,
                'dimension_height' => 20,
                'status' => 'in_transit',
                'logs' => [
                    ['status_description' => 'Barang diterima di kantor cabang Jakarta', 'location' => 'Jakarta', 'timestamp' => now()->subDays(2)],
                    ['status_description' => 'Barang sedang dalam perjalanan menuju Medan', 'location' => 'Pekanbaru', 'timestamp' => now()->subDay()],
                ],
            ],
            [
                'awb_number' => 'AWB_ATL_20250103001',
                'sender_name' => 'Susi Rahayu',
                'receiver_name' => 'Rauf Hidayat',
                'origin' => 'Surabaya',
                'destination' => 'Makassar',
                'service_type' => 'udara',
                'weight' => 22.3,
                'dimension_length' => 60,
                'dimension_width' => 45,
                'dimension_height' => 35,
                'status' => 'pending',
                'logs' => [
                    ['status_description' => 'Data pengiriman telah dibuat, menunggu proses pengangkutan', 'location' => 'Surabaya', 'timestamp' => now()],
                ],
            ],
            [
                'awb_number' => 'AWB_ATL_20250104001',
                'sender_name' => 'PT Indojaya',
                'receiver_name' => 'Komang Surya',
                'origin' => 'Jakarta',
                'destination' => 'Bali',
                'service_type' => 'udara',
                'weight' => 5.0,
                'dimension_length' => 40,
                'dimension_width' => 30,
                'dimension_height' => 20,
                'status' => 'delivered',
                'logs' => [
                    ['status_description' => 'Barang diterima di kantor cabang Jakarta', 'location' => 'Jakarta', 'timestamp' => now()->subDays(4)],
                    ['status_description' => 'Barang sedang dalam perjalanan menuju Bali', 'location' => 'Banyuwangi', 'timestamp' => now()->subDays(3)],
                    ['status_description' => 'Barang telah diterima oleh penerima', 'location' => 'Bali', 'timestamp' => now()->subDays(2)],
                ],
            ],
            [
                'awb_number' => 'AWB_ATL_20250105001',
                'sender_name' => 'Rina Wati',
                'receiver_name' => 'Agus Salim',
                'origin' => 'Bandung',
                'destination' => 'Semarang',
                'service_type' => 'darat',
                'weight' => 10.0,
                'dimension_length' => 45,
                'dimension_width' => 35,
                'dimension_height' => 25,
                'status' => 'in_transit',
                'logs' => [
                    ['status_description' => 'Barang diterima di kantor cabang Bandung', 'location' => 'Bandung', 'timestamp' => now()->subDay()],
                    ['status_description' => 'Barang sedang dalam perjalanan menuju Semarang', 'location' => 'Cirebon', 'timestamp' => now()],
                ],
            ],
        ];

        foreach ($shipments as $data) {
            $shipment = Shipment::updateOrCreate(
                ['awb_number' => $data['awb_number']],
                collect($data)->except('logs')->toArray()
            );

            $shipment->logs()->delete();

            foreach ($data['logs'] as $log) {
                ShipmentLog::create([
                    'shipment_id' => $shipment->id,
                    'status_description' => $log['status_description'],
                    'location' => $log['location'],
                    'timestamp' => $log['timestamp'],
                ]);
            }
        }
    }
}
