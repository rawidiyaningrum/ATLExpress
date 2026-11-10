<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manage_company_print_settings')
                ->label('Pengaturan NPWP & Rekening Perusahaan')
                ->icon('heroicon-o-building-office')
                ->color('primary')
                ->modalHeading('Pengaturan NPWP & Rekening Perusahaan (Kop Cetak Dokumen)')
                ->modalDescription('Atur nomor NPWP dan data rekening bank tujuan pembayaran untuk dokumen cetak ATL Express dan PT Douze.')
                ->modalSubmitActionLabel('Simpan Pengaturan')
                ->fillForm(fn (): array => [
                    'npwp_atl' => Setting::getValue('npwp_atl', ''),
                    'bank_name_atl' => Setting::getValue('bank_name_atl', 'Bank BCA'),
                    'bank_account_number_atl' => Setting::getValue('bank_account_number_atl', '604.3792.330'),
                    'bank_account_name_atl' => Setting::getValue('bank_account_name_atl', 'PT ANGKASA TRANS LOGISTIK'),

                    'npwp_douze' => Setting::getValue('npwp_douze', ''),
                    'bank_name_douze' => Setting::getValue('bank_name_douze', 'Bank BCA'),
                    'bank_account_number_douze' => Setting::getValue('bank_account_number_douze', ''),
                    'bank_account_name_douze' => Setting::getValue('bank_account_name_douze', 'PT DOUZE'),
                ])
                ->form([
                    Forms\Components\Tabs::make('Perusahaan')
                        ->tabs([
                            Forms\Components\Tabs\Tab::make('ATL Express')
                                ->icon('heroicon-m-truck')
                                ->schema([
                                    Forms\Components\TextInput::make('npwp_atl')
                                        ->label('NPWP ATL Express')
                                        ->placeholder('Contoh: 01.234.567.8-012.000'),

                                    Forms\Components\TextInput::make('bank_name_atl')
                                        ->label('Nama Bank ATL Express')
                                        ->placeholder('Contoh: Bank BCA')
                                        ->datalist(['Bank BCA', 'Bank Mandiri', 'Bank BRI', 'Bank BNI']),

                                    Forms\Components\TextInput::make('bank_account_number_atl')
                                        ->label('Nomor Rekening ATL Express')
                                        ->placeholder('Contoh: 604.3792.330'),

                                    Forms\Components\TextInput::make('bank_account_name_atl')
                                        ->label('Atas Nama Rekening ATL Express')
                                        ->placeholder('Contoh: PT ANGKASA TRANS LOGISTIK'),
                                ])->columns(2),

                            Forms\Components\Tabs\Tab::make('PT Douze')
                                ->icon('heroicon-m-building-office-2')
                                ->schema([
                                    Forms\Components\TextInput::make('npwp_douze')
                                        ->label('NPWP PT Douze')
                                        ->placeholder('Contoh: 98.765.432.1-098.000'),

                                    Forms\Components\TextInput::make('bank_name_douze')
                                        ->label('Nama Bank PT Douze')
                                        ->placeholder('Contoh: Bank BCA')
                                        ->datalist(['Bank BCA', 'Bank Mandiri', 'Bank BRI', 'Bank BNI']),

                                    Forms\Components\TextInput::make('bank_account_number_douze')
                                        ->label('Nomor Rekening PT Douze')
                                        ->placeholder('Contoh: 1234567890'),

                                    Forms\Components\TextInput::make('bank_account_name_douze')
                                        ->label('Atas Nama Rekening PT Douze')
                                        ->placeholder('Contoh: PT DOUZE'),
                                ])->columns(2),
                        ]),
                ])
                ->action(function (array $data): void {
                    foreach ($data as $key => $value) {
                        Setting::updateOrCreate(
                            ['key' => $key],
                            ['value' => $value ?? '']
                        );
                    }

                    Notification::make()
                        ->title('Pengaturan Berhasil Disimpan')
                        ->body('Data NPWP dan Nomor Rekening untuk ATL Express dan PT Douze berhasil diperbarui.')
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make()->label('Tambah Setting Manual'),
        ];
    }
}
