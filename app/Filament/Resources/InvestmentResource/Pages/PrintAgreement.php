<?php

namespace App\Filament\Resources\InvestmentResource\Pages;

use App\Filament\Resources\InvestmentResource;
use App\Models\Investment;
use App\Models\Setting;
use App\Support\CompanyProfile;
use App\Support\NumberToWordsId;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

class PrintAgreement extends Page
{
    use InteractsWithRecord;

    protected static string $resource = InvestmentResource::class;

    protected static ?string $title = 'Perjanjian Investasi';

    protected static ?string $breadcrumb = 'Perjanjian Investasi';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.investments.print-agreement';

    #[Url(as: 'company')]
    public string $company = CompanyProfile::ATL;

    public function mount($record): void
    {
        abort_unless(auth()->user()?->isAdmin() ?? false, 403);

        $this->record = $this->resolveRecord($record);

        $this->record->loadMissing(['investor', 'shipment']);

        if (! in_array($this->company, [CompanyProfile::ATL, CompanyProfile::DOUZE], true)) {
            $this->company = CompanyProfile::ATL;
        }
    }

    public function setCompany(string $company): void
    {
        if (in_array($company, [CompanyProfile::ATL, CompanyProfile::DOUZE], true)) {
            $this->company = $company;
        }
    }

    public function getTitle(): string
    {
        return 'PERJANJIAN INVESTASI - '.($this->record->investor?->name ?? '');
    }

    protected function getViewData(): array
    {
        /** @var Investment $investment */
        $investment = $this->record;
        $investor = $investment->investor;
        $shipment = $investment->shipment;

        $investmentDate = $investment->investment_date ?? $investment->created_at ?? now();
        $agreementDate = $investmentDate;

        // Alokasi dana investasi: nomor AWB / rute bila terkait shipment, atau nama proyek.
        $allocation = $investment->project_name ?: 'Proyek Umum';
        if ($shipment) {
            $allocation = trim(
                ($shipment->awb_number ?: '').
                ($shipment->origin || $shipment->destination
                    ? ' ('.($shipment->origin ?: '-').' → '.($shipment->destination ?: '-').')'
                    : '')
            );
        }

        $amount = (float) $investment->amount;
        $profitSharingAmount = (float) $investment->profit_sharing_amount;

        return [
            'investment' => $investment,
            'investor' => $investor,
            'shipment' => $shipment,
            'company' => $this->company,
            'companyProfile' => CompanyProfile::get($this->company),
            'agreement_number' => $this->buildAgreementNumber($investment, $investmentDate),
            'agreement_date' => $agreementDate,
            'agreement_date_long' => $agreementDate->translatedFormat('d F Y'),
            'investor_name' => $investor?->name ?? '-',
            'investor_ktp' => $investor?->ktp_number ?: '-',
            'investor_address' => $investor?->address ?: '-',
            'investor_phone' => $investor?->phone ?: '-',
            'investor_relation' => $investor?->relation ?: null,
            'investor_bank' => $investor?->bank_name
                ? trim($investor->bank_name.' '.($investor->bank_account_number ?: '').' a/n '.($investor->bank_account_name ?: $investor->name))
                : null,
            'amount' => $amount,
            'amount_formatted' => 'Rp '.number_format($amount, 0, ',', '.'),
            'amount_words' => trim((new NumberToWordsId)->convert($amount)),
            'profit_sharing_percentage' => (float) $investment->profit_sharing_percentage,
            'profit_sharing_amount' => $profitSharingAmount,
            'profit_sharing_amount_formatted' => 'Rp '.number_format($profitSharingAmount, 0, ',', '.'),
            'profit_sharing_words' => trim((new NumberToWordsId)->convert($profitSharingAmount)),
            'profit_sharing_date' => $investment->profit_sharing_date,
            'profit_sharing_date_long' => $investment->profit_sharing_date?->translatedFormat('d F Y'),
            'capital_payout_date_long' => $investment->capital_payout_date?->translatedFormat('d F Y'),
            'allocation' => $allocation,
            'notes' => $investment->notes,
            'signer_name' => Setting::getValue('receipt_signer_name') ?: 'Harimsa S.H',
            'city_date' => 'Jakarta, '.$agreementDate->translatedFormat('d F Y'),
        ];
    }

    /**
     * Nomor perjanjian dengan format: IVT/{kode perusahaan}/{dd}/{mm}/{yyyy}/{no_urut}.
     */
    protected function buildAgreementNumber(Investment $investment, Carbon $date): string
    {
        if ($investment->investment_date) {
            $sequence = Investment::query()
                ->whereYear('investment_date', $date->year)
                ->whereMonth('investment_date', $date->month)
                ->where('id', '<=', $investment->id)
                ->count();
        } else {
            $sequence = Investment::query()->where('id', '<=', $investment->id)->count();
        }

        if ($sequence < 1) {
            $sequence = 1;
        }

        return implode('/', [
            'IVT',
            $this->company,
            $date->format('d'),
            $date->format('m'),
            $date->format('Y'),
            str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->alpineClickHandler('window.print()'),
        ];
    }
}
