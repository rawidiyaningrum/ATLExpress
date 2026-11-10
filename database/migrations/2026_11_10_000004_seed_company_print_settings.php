<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            'npwp_atl' => '-',
            'bank_name_atl' => 'Bank BCA',
            'bank_account_number_atl' => '604.3792.330',
            'bank_account_name_atl' => 'PT ANGKASA TRANS LOGISTIK',

            'npwp_douze' => '-',
            'bank_name_douze' => 'Bank BCA',
            'bank_account_number_douze' => '-',
            'bank_account_name_douze' => 'PT DOUZE',
        ];

        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    public function down(): void
    {
        // Don't delete settings on rollback to avoid losing user-edited data
    }
};
