<?php

namespace App\Filament\Resources\ThreadsPostResource\Pages;

use App\Filament\Resources\ShippingRequestResource;
use App\Filament\Resources\ThreadsPostResource;
use App\Models\ThreadsPost;
use Filament\Actions;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewThreadsPost extends ViewRecord
{
    protected static string $resource = ThreadsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bukaThreads')
                ->label('Buka di Threads')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn (): ?string => $this->record->permalink)
                ->openUrlInNewTab()
                ->visible(fn (): bool => ! empty($this->record->permalink)),

            Actions\Action::make('contacted')
                ->label('Tandai Dihubungi')
                ->icon('heroicon-o-phone')
                ->color('info')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => ThreadsPost::STATUS_CONTACTED]))
                ->visible(fn (): bool => $this->record->status === ThreadsPost::STATUS_NEW),

            Actions\Action::make('jadikanPermintaan')
                ->label('Jadikan Permintaan Pengiriman')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->modalHeading('Buat Permintaan Pengiriman')
                ->modalDescription(fn (): string => 'Dari postingan @'.($this->record->username ?? '-').'. Lengkapi data yang belum tersedia.')
                ->form(ThreadsPostResource::conversionForm())
                ->fillForm(fn (): array => ThreadsPostResource::conversionDefaults($this->record))
                ->action(function (array $data): void {
                    ThreadsPostResource::convertToShippingRequest($this->record, $data);

                    Notification::make()
                        ->title('Permintaan pengiriman dibuat')
                        ->body('Postingan ditandai sudah dihubungi.')
                        ->success()
                        ->actions([
                            NotificationAction::make('buka')
                                ->label('Buka Permintaan')
                                ->url(ShippingRequestResource::getUrl('index'))
                                ->button(),
                        ])
                        ->send();
                })
                ->visible(fn (): bool => $this->record->status !== ThreadsPost::STATUS_WON),

            Actions\Action::make('won')
                ->label('Jadi Pelanggan')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => ThreadsPost::STATUS_WON]))
                ->visible(fn (): bool => $this->record->status !== ThreadsPost::STATUS_WON),

            Actions\Action::make('ignored')
                ->label('Diabaikan')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => ThreadsPost::STATUS_IGNORED]))
                ->visible(fn (): bool => $this->record->status !== ThreadsPost::STATUS_IGNORED),
        ];
    }
}
