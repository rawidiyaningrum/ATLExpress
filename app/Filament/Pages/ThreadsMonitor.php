<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\OnlyAdmins;
use App\Filament\Resources\ThreadsKeywordResource;
use App\Filament\Resources\ThreadsPostResource;
use App\Jobs\FetchThreadsCargoRequestsJob;
use App\Models\ThreadsKeyword;
use App\Models\ThreadsPost;
use App\Services\AI\AiException;
use App\Services\Threads\ThreadsKeywordResearcher;
use App\Services\Threads\ThreadsQueryQuota;
use App\Services\Threads\ThreadsSourceManager;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ThreadsMonitor extends Page
{
    use OnlyAdmins;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Monitor Threads';

    protected static ?string $title = 'Monitor Permintaan Kargo Threads';

    protected static ?string $slug = 'monitor-threads';

    protected static string $view = 'filament.pages.threads-monitor';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdminRole() ?? false;
    }

    /**
     * Tema opsional untuk saran keyword AI.
     */
    public string $seed = '';

    public int $suggestCount = 15;

    /**
     * Kandidat keyword hasil AI yang belum disimpan.
     *
     * @var array<int, array{keyword: string, mode: string}>
     */
    public array $suggestions = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetchNow')
                ->label('Fetch Sekarang')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Job pengambilan data akan dikirim ke queue. Pastikan queue worker aktif.')
                ->action('runFetch'),
        ];
    }

    public function runFetch(): void
    {
        if (! config('threads.enabled')) {
            Notification::make()
                ->title('Fitur Threads nonaktif')
                ->body('Aktifkan THREADS_ENABLED=true di .env server.')
                ->danger()
                ->send();

            return;
        }

        $ready = collect(app(ThreadsSourceManager::class)->resolve())
            ->contains(fn ($source): bool => $source->isAvailable());

        if (! $ready) {
            Notification::make()
                ->title('Belum ada sumber yang siap')
                ->body('Isi THREADS_ACCESS_TOKEN (API resmi) atau konfigurasi fallback scraping terlebih dahulu.')
                ->danger()
                ->send();

            return;
        }

        if (ThreadsKeyword::query()->active()->doesntExist()) {
            Notification::make()
                ->title('Belum ada keyword aktif')
                ->body('Tambahkan keyword di menu Keyword Threads terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        FetchThreadsCargoRequestsJob::dispatch();

        Notification::make()
            ->title('Pengambilan data dikirim ke queue')
            ->body('Postingan baru akan muncul di menu Permintaan Kargo Threads.')
            ->success()
            ->send();
    }

    public function suggestKeywords(): void
    {
        try {
            $this->suggestions = app(ThreadsKeywordResearcher::class)
                ->suggest((string) $this->seed, (int) $this->suggestCount);
        } catch (AiException $e) {
            Notification::make()
                ->title('Gagal membuat saran keyword')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        if ($this->suggestions === []) {
            Notification::make()
                ->title('Tidak ada saran keyword')
                ->body('Coba ubah tema atau ulangi beberapa saat lagi.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title(count($this->suggestions).' saran keyword dibuat')
            ->body('Tinjau lalu tambahkan sebagai keyword (nonaktif).')
            ->success()
            ->send();
    }

    public function storeSuggestions(): void
    {
        $added = 0;

        foreach ($this->suggestions as $item) {
            $keyword = trim((string) ($item['keyword'] ?? ''));
            $mode = ($item['mode'] ?? ThreadsKeyword::MODE_KEYWORD) === ThreadsKeyword::MODE_TAG
                ? ThreadsKeyword::MODE_TAG
                : ThreadsKeyword::MODE_KEYWORD;

            if ($keyword === '') {
                continue;
            }

            $exists = ThreadsKeyword::query()
                ->where('keyword', $keyword)
                ->where('mode', $mode)
                ->exists();

            if ($exists) {
                continue;
            }

            ThreadsKeyword::create([
                'keyword' => $keyword,
                'mode' => $mode,
                'is_active' => false,
                'source' => 'ai',
            ]);

            $added++;
        }

        $this->suggestions = [];

        Notification::make()
            ->title($added.' keyword ditambahkan')
            ->body('Statusnya nonaktif. Aktifkan dari menu Keyword Threads agar ikut diambil saat fetch.')
            ->success()
            ->send();
    }

    public function clearSuggestions(): void
    {
        $this->suggestions = [];
    }

    public function getViewData(): array
    {
        $quota = app(ThreadsQueryQuota::class);
        $manager = app(ThreadsSourceManager::class);

        return array_merge(parent::getViewData(), [
            'enabled' => (bool) config('threads.enabled'),
            'sourceLabel' => $manager->label(),
            'apiConfigured' => trim((string) config('threads.api.access_token')) !== '',
            'scrapeConfigured' => trim((string) config('threads.scrape.endpoint')) !== ''
                || trim((string) config('threads.scrape.doc_id')) !== ''
                || (bool) config('threads.scrape.allow_page'),
            'aiConfigured' => app(ThreadsKeywordResearcher::class)->isConfigured(),
            'quotaUsed' => $quota->used(),
            'quotaRemaining' => $quota->remaining(),
            'quotaCap' => $quota->cap(),
            'activeKeywords' => ThreadsKeyword::query()->active()->count(),
            'totalKeywords' => ThreadsKeyword::count(),
            'totalPosts' => ThreadsPost::count(),
            'newPosts' => ThreadsPost::query()->status(ThreadsPost::STATUS_NEW)->count(),
            'todayPosts' => ThreadsPost::query()->whereDate('posted_at', today())->count(),
            'lastRunAt' => ThreadsKeyword::query()->max('last_run_at'),
            'recentPosts' => ThreadsPost::query()->newest()->limit(10)->get(),
            'agentUrl' => ThreadsPostResource::getUrl('index'),
            'keywordUrl' => ThreadsKeywordResource::getUrl('index'),
        ]);
    }
}
