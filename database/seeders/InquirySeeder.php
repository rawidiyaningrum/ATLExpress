<?php

namespace Database\Seeders;

use App\Models\Inquiry;
use Illuminate\Database\Seeder;

class InquirySeeder extends Seeder
{
    public function run(): void
    {
        $inquiries = [
            [
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@example.com',
                'phone' => '081234567890',
                'subject' => 'Informasi Tarif Pengiriman',
                'message' => 'Selamat siang, saya ingin menanyakan tarif pengiriman kargo dari Jakarta ke Surabaya dengan berat sekitar 50 kg. Berapa estimasi biaya dan lama pengirimannya? Terima kasih.',
                'is_read' => false,
            ],
            [
                'name' => 'Rina Wati',
                'email' => 'rina.wati@example.com',
                'phone' => '082198765432',
                'subject' => 'Cek Status Pengiriman',
                'message' => 'Saya ingin bertanya mengenai status pengiriman paket saya dengan nomor resi ATL-2025-000002. Sampai saat ini statusnya masih dalam perjalanan, mohon informasinya. Terima kasih.',
                'is_read' => false,
            ],
            [
                'name' => 'Ahmad Hidayat',
                'email' => 'ahmad.hidayat@example.com',
                'phone' => '083812345678',
                'subject' => 'Penawaran Kerjasama',
                'message' => 'Saya mewakili sebuah toko online yang sedang berkembang dan ingin menawarkan kerjasama pengiriman dengan ATL Express. Mohon hubungi saya untuk diskusi lebih lanjut mengenai kemungkinan kerjasama dan tarif khusus. Terima kasih.',
                'is_read' => false,
            ],
        ];

        foreach ($inquiries as $inquiry) {
            Inquiry::create($inquiry);
        }
    }
}