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
                'tracking_number' => 'ATL-2025-000001',
                'sender_name' => 'PT Sinar Maju',
                'receiver_name' => 'CV Karya Abadi',
                'origin' => 'Jakarta',
                'destination' => 'Surabaya',
                'weight' => 15.5,
                'status' => 'delivered',
                'logs' => [
                    ['status_description' => 'Barang diterima di kantor cabang Jakarta', 'location' => 'Jakarta', 'timestamp' => now()->subDays(3)],
                    ['status_description' => 'Barang sedang dalam perjalanan menuju Surabaya', 'location' => 'Cikampek', 'timestamp' => now()->subDays(2)],
                    ['status_description' => 'Barang telah diterima oleh penerima', 'location' => 'Surabaya', 'timestamp' => now()->subDay()],
                ],
            ],
            [
                'tracking_number' => 'ATL-2025-000002',
                'sender_name' => 'Budi Santoso',
                'receiver_name' => 'Andi Pratama',
                'origin' => 'Jakarta',
                'destination' => 'Medan',
                'weight' => 8.0,
                'status' => 'in_transit',
                'logs' => [
                    ['status_description' => 'Barang diterima di kantor cabang Jakarta', 'location' => 'Jakarta', 'timestamp' => now()->subDays(2)],
                    ['status_description' => 'Barang sedang dalam perjalanan menuju Medan', 'location' => 'Pekanbaru', 'timestamp' => now()->subDay()],
                ],
            ],
            [
                'tracking_number' => 'ATL-2025-000003',
                'sender_name' => 'Susi Rahayu',
                'receiver_name' => 'Rauf Hidayat',
                'origin' => 'Surabaya',
                'destination' => 'Makassar',
                'weight' => 22.3,
                'status' => 'pending',
                'logs' => [
                    ['status_description' => 'Data pengiriman telah dibuat, menunggu proses pengangkutan', 'location' => 'Surabaya', 'timestamp' => now()],
                ],
            ],
            [
                'tracking_number' => 'ATL-2025-000004',
                'sender_name' => 'PT Indojaya',
                'receiver_name' => 'Komang Surya',
                'origin' => 'Jakarta',
                'destination' => 'Bali',
                'weight' => 5.0,
                'status' => 'delivered',
                'logs' => [
                    ['status_description' => 'Barang diterima di kantor cabang Jakarta', 'location' => 'Jakarta', 'timestamp' => now()->subDays(4)],
                    ['status_description' => 'Barang sedang dalam perjalanan menuju Bali', 'location' => 'Banyuwangi', 'timestamp' => now()->subDays(3)],
                    ['status_description' => 'Barang telah diterima oleh penerima', 'location' => 'Bali', 'timestamp' => now()->subDays(2)],
                ],
            ],
            [
                'tracking_number' => 'ATL-2025-000005',
                'sender_name' => 'Rina Wati',
                'receiver_name' => 'Agus Salim',
                'origin' => 'Bandung',
                'destination' => 'Semarang',
                'weight' => 10.0,
                'status' => 'in_transit',
                'logs' => [
                    ['status_description' => 'Barang diterima di kantor cabang Bandung', 'location' => 'Bandung', 'timestamp' => now()->subDay()],
                    ['status_description' => 'Barang sedang dalam perjalanan menuju Semarang', 'location' => 'Cirebon', 'timestamp' => now()],
                ],
            ],
        ];

        foreach ($shipments as $data) {
            $shipment = Shipment::updateOrCreate(
                ['tracking_number' => $data['tracking_number']],
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