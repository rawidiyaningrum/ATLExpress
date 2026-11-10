<?php

namespace Tests\Feature;

use App\Filament\Resources\SettingResource\Pages\ListSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\CompanyProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_company_print_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(ListSettings::class)
            ->assertActionVisible('manage_company_print_settings')
            ->callAction('manage_company_print_settings', [
                'npwp_atl' => '11.222.333.4-555.000',
                'bank_name_atl' => 'Bank BCA',
                'bank_account_number_atl' => '604.3792.330',
                'bank_account_name_atl' => 'PT ANGKASA TRANS LOGISTIK',

                'npwp_douze' => '99.888.777.6-555.000',
                'bank_name_douze' => 'Bank BNI',
                'bank_account_number_douze' => '0987654321',
                'bank_account_name_douze' => 'PT DOUZE INDONESIA',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('11.222.333.4-555.000', Setting::getValue('npwp_atl'));
        $this->assertSame('99.888.777.6-555.000', Setting::getValue('npwp_douze'));
        $this->assertSame('Bank BNI', Setting::getValue('bank_name_douze'));
        $this->assertSame('0987654321', Setting::getValue('bank_account_number_douze'));
        $this->assertSame('PT DOUZE INDONESIA', Setting::getValue('bank_account_name_douze'));

        $atlProfile = CompanyProfile::get('atl');
        $this->assertSame('11.222.333.4-555.000', $atlProfile['npwp']);
        $this->assertSame('Bank BCA', $atlProfile['bank_name']);

        $douzeProfile = CompanyProfile::get('douze');
        $this->assertSame('99.888.777.6-555.000', $douzeProfile['npwp']);
        $this->assertSame('Bank BNI', $douzeProfile['bank_name']);
        $this->assertSame('0987654321', $douzeProfile['bank_account_number']);
        $this->assertSame('PT DOUZE INDONESIA', $douzeProfile['bank_account_name']);
    }
}
