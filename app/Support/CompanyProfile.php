<?php

namespace App\Support;

use App\Models\Setting;

class CompanyProfile
{
    public const ATL = 'atl';

    public const DOUZE = 'douze';

    public const DEFAULT_ADDRESS = 'Graha Buana , Jl. Dr. Saharjo No.210A, RT.5/RW.9, Menteng Dalam, Kec. Tebet, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12870';

    /**
     * Mengambil profil lengkap perusahaan (logo, nama, alamat, NPWP, rekening bank)
     * berdasarkan kode perusahaan ('atl' atau 'douze').
     *
     * @return array<string, string>
     */
    public static function get(string $company = self::ATL): array
    {
        if ($company === self::DOUZE) {
            return [
                'code' => self::DOUZE,
                'name' => 'PT Douze',
                'full_name' => 'PT Douze (Multimedia, Logistik, Organizer)',
                'tagline' => 'Multimedia, Logistik, Organizer',
                'logo' => asset('images/logo_douze.jpeg'),
                'address' => Setting::getValue('address_douze') ?: Setting::getValue('address', self::DEFAULT_ADDRESS),
                'npwp' => (string) Setting::getValue('npwp_douze', '-'),
                'bank_name' => (string) Setting::getValue('bank_name_douze', 'Bank BCA'),
                'bank_account_number' => (string) Setting::getValue('bank_account_number_douze', '-'),
                'bank_account_name' => (string) Setting::getValue('bank_account_name_douze', 'PT DOUZE'),
                'confirmation_text' => 'Mohon konfirmasi pembayaran ke rekening PT Douze.',
            ];
        }

        return [
            'code' => self::ATL,
            'name' => 'ATL Express',
            'full_name' => (string) (Setting::getValue('company_name') ?: 'ATL Express'),
            'tagline' => (string) (Setting::getValue('company_tagline') ?: 'Cargo & Logistics'),
            'logo' => asset('images/logo.png'),
            'address' => Setting::getValue('address_atl') ?: Setting::getValue('address', self::DEFAULT_ADDRESS),
            'npwp' => (string) Setting::getValue('npwp_atl', '-'),
            'bank_name' => (string) Setting::getValue('bank_name_atl', 'Bank BCA'),
            'bank_account_number' => (string) Setting::getValue('bank_account_number_atl', '604.3792.330'),
            'bank_account_name' => (string) Setting::getValue('bank_account_name_atl', 'PT ANGKASA TRANS LOGISTIK'),
            'confirmation_text' => 'Mohon konfirmasi pembayaran ke rekening ATL Express.',
        ];
    }
}
