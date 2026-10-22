<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\OnlyAdmins;
use App\Filament\Resources\PostResource;
use App\Models\ArticleGenerationBatch;
use App\Models\Category;
use App\Models\Setting;
use App\Services\AI\AiBatchRunner;
use App\Services\AI\GeminiClient;
use App\Services\ArticleSeoGenerator;
use App\Services\ArticleTemplateGenerator as ArticleTemplateGeneratorService;
use App\Services\AiBudget;
use App\Services\AiCostEstimator;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class ArticleTemplateGenerator extends Page
{
    use OnlyAdmins;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Management';

    protected static ?string $navigationLabel = 'Generator Artikel';

    protected static ?string $title = 'Generator Artikel';

    protected static ?string $slug = 'generator-artikel';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.article-template-generator';

    public ?array $data = [];

    public ?string $activeBatchId = null;

    public function mount(): void
    {
        $this->form->fill([
            'category_slug' => 'berita',
            'count' => 1,
            'content_source' => 'template',
            'ai_model' => (string) config('ai.model'),
            'ai_fallback' => true,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Artikel')
                    ->description('Buat artikel blog langsung ter-publish berdasarkan keyword. Pilih sumber konten di bawah.')
                    ->schema([
                        Forms\Components\TextInput::make('keyword')
                            ->label('Keyword')
                            ->placeholder('Contoh: ekspedisi kargo jakarta')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->helperText('Masukkan satu keyword utama untuk topik artikel.'),
                        Forms\Components\Select::make('category_slug')
                            ->label('Kategori')
                            ->options(fn () => Category::query()->pluck('name', 'slug')->all())
                            ->required()
                            ->searchable()
                            ->live(),
                        Forms\Components\TextInput::make('count')
                            ->label('Jumlah Artikel')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->default(1)
                            ->required()
                            ->helperText('Maksimal 50 artikel per generate. Slug duplikat akan dilewati.'),
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Waktu Terbit')
                            ->seconds(false)
                            ->default(now())
                            ->helperText('Kosongkan bila ingin langsung terbit sekarang.'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Sumber Konten')
                    ->description('AI menulis isi artikel sekaligus metadata SEO-nya. Template memakai pola bawaan tanpa biaya.')
                    ->schema([
                        Forms\Components\Radio::make('content_source')
                            ->label('Sumber Konten')
                            ->options([
                                'template' => 'Template (tanpa AI, tanpa biaya)',
                                'ai' => 'AI Gemini (isi + SEO, ada biaya token)',
                            ])
                            ->default('template')
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('ai_model')
                            ->label('Model AI')
                            ->options(fn () => config('ai.models'))
                            ->default(fn () => (string) config('ai.model'))
                            ->visible(fn (Get $get): bool => $get('content_source') === 'ai')
                            ->required(fn (Get $get): bool => $get('content_source') === 'ai'),
                        Forms\Components\Toggle::make('ai_fallback')
                            ->label('Fallback ke template bila AI gagal')
                            ->helperText('Bila AI gagal atau budget habis, artikel tetap dibuat dari template supaya batch selesai.')
                            ->default(true)
                            ->visible(fn (Get $get): bool => $get('content_source') === 'ai'),
                        Forms\Components\Placeholder::make('ai_info')
                            ->label('Status AI & Estimasi Biaya')
                            ->visible(fn (Get $get): bool => $get('content_source') === 'ai')
                            ->content(fn (Get $get): HtmlString => $this->aiInfoHtml($get)),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('SEO (auto-generate per artikel)')
                    ->description('Diisi otomatis per artikel berdasarkan keyword, judul, lead, dan heading. Bisa di-override manual. Gunakan placeholder {keyword}, {title}, {index}.')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Meta Title')
                            ->maxLength(ArticleSeoGenerator::TITLE_LIMIT)
                            ->helperText('Kosongkan untuk auto. Maks '.ArticleSeoGenerator::TITLE_LIMIT.' karakter (termasuk brand).'),
                        Forms\Components\Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->maxLength(ArticleSeoGenerator::DESCRIPTION_LIMIT)
                            ->rows(3)
                            ->helperText('Kosongkan untuk auto. Maks '.ArticleSeoGenerator::DESCRIPTION_LIMIT.' karakter.'),
                        Forms\Components\Textarea::make('meta_keywords')
                            ->label('Meta Keywords')
                            ->rows(2)
                            ->helperText('Pisahkan dengan koma. Kosongkan untuk auto-generate per artikel.'),
                        Forms\Components\Placeholder::make('seo_preview')
                            ->label('Preview SEO (artikel pertama)')
                            ->content(function (Get $get): HtmlString {
                                $keyword = trim((string) ($get('keyword') ?? ''));

                                if ($keyword === '') {
                                    return new HtmlString('<span class="text-gray-500">Isi keyword untuk melihat preview.</span>');
                                }

                                $preview = app(ArticleTemplateGeneratorService::class)->preview(
                                    $keyword,
                                    (string) ($get('category_slug') ?? 'berita'),
                                );

                                $brand = trim((string) (Setting::getValue('company_name') ?: 'ATL Express'));
                                $metaTitle = (string) $preview['meta_title'];
                                $metaDescription = (string) $preview['meta_description'];
                                $metaKeywords = (string) $preview['meta_keywords'];

                                $html = '<div class="space-y-1 text-sm">';
                                $html .= '<div><span class="font-medium">Title:</span> '.e($metaTitle).'</div>';
                                $html .= '<div><span class="font-medium">Description:</span> '.e($metaDescription).'</div>';
                                $html .= '<div><span class="font-medium">Keywords:</span> '.e($metaKeywords).'</div>';
                                $html .= '<div class="text-xs text-gray-500">Brand: '.e($brand).'</div>';
                                $html .= '</div>';

                                return new HtmlString($html);
                            }),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('generate')
                ->label(fn (): string => ($this->data['content_source'] ?? 'template') === 'ai'
                    ? 'Generate dengan AI'
                    : 'Generate & Publish')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->action('generateArticles'),
        ];
    }

    public function generateArticles(): void
    {
        $data = $this->form->getState();

        $keyword = trim((string) ($data['keyword'] ?? ''));
        $categorySlug = (string) ($data['category_slug'] ?? 'berita');
        $count = (int) ($data['count'] ?? 1);
        $publishedAt = $data['published_at'] ?? now();

        if ($keyword === '') {
            Notification::make()
                ->title('Keyword wajib diisi')
                ->danger()
                ->send();

            return;
        }

        if (($data['content_source'] ?? 'template') === 'ai') {
            $this->generateWithAi($keyword, $categorySlug, $count, $publishedAt, $data);

            return;
        }

        $result = app(ArticleTemplateGeneratorService::class)->generate($keyword, $categorySlug, $count, [
            'published_at' => $publishedAt,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
        ]);

        if ($result['generated'] === 0 && $result['skipped'] === 0) {
            Notification::make()
                ->title('Tidak ada artikel yang dibuat')
                ->body('Periksa keyword atau kategori yang dipilih.')
                ->warning()
                ->send();

            return;
        }

        if ($result['generated'] === 0) {
            Notification::make()
                ->title('Semua slug duplikat')
                ->body("Dilewati {$result['skipped']} artikel karena slug sudah ada di database.")
                ->warning()
                ->send();

            return;
        }

        $body = "Berhasil generate {$result['generated']} artikel langsung ter-publish.";
        if ($result['skipped'] > 0) {
            $body .= " Dilewati {$result['skipped']} karena slug sudah ada.";
        }

        Notification::make()
            ->title('Artikel berhasil dibuat')
            ->body($body)
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('lihat')
                    ->label('Lihat di Daftar Artikel')
                    ->url(PostResource::getUrl('index'))
                    ->button(),
            ])
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function generateWithAi(string $keyword, string $categorySlug, int $count, mixed $publishedAt, array $data): void
    {
        if (! app(GeminiClient::class)->isConfigured()) {
            Notification::make()
                ->title('AI belum dikonfigurasi')
                ->body('Isi AI_API_KEY di .env server dan pastikan AI_ENABLED=true.')
                ->danger()
                ->send();

            return;
        }

        $result = app(AiBatchRunner::class)->dispatch(
            $keyword,
            $categorySlug,
            $count,
            [
                'published_at' => $publishedAt,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
            ],
            (string) ($data['ai_model'] ?: config('ai.model')),
            (bool) ($data['ai_fallback'] ?? true),
        );

        if (! $result['allowed']) {
            Notification::make()
                ->title('Generate AI dibatalkan')
                ->body($result['reason'])
                ->danger()
                ->send();

            return;
        }

        $this->activeBatchId = $result['batch']->uuid;

        Notification::make()
            ->title("{$result['count']} artikel dijadwalkan")
            ->body('Perkiraan biaya USD '.number_format($result['estimated_cost'], 4).'. Artikel dibuat di background oleh worker queue.')
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('lihat')
                    ->label('Lihat di Daftar Artikel')
                    ->url(PostResource::getUrl('index'))
                    ->button(),
            ])
            ->send();
    }

    public function getViewData(): array
    {
        $batch = $this->currentBatch();

        return array_merge(parent::getViewData(), [
            'batchProgress' => $batch !== null ? $this->renderBatchProgress($batch) : '',
            'batchActive' => $batch !== null && ! $batch->isFinished(),
        ]);
    }

    private function currentBatch(): ?ArticleGenerationBatch
    {
        if ($this->activeBatchId !== null) {
            $batch = ArticleGenerationBatch::query()->where('uuid', $this->activeBatchId)->first();

            if ($batch !== null) {
                return $batch;
            }
        }

        return ArticleGenerationBatch::query()->latest('id')->first();
    }

    private function renderBatchProgress(ArticleGenerationBatch $batch): string
    {
        $percent = $batch->total > 0 ? (int) round($batch->processed / $batch->total * 100) : 0;

        $status = match ($batch->status) {
            ArticleGenerationBatch::STATUS_QUEUED => 'Menunggu worker queue',
            ArticleGenerationBatch::STATUS_RUNNING => 'Sedang berjalan',
            ArticleGenerationBatch::STATUS_COMPLETED => 'Selesai',
            ArticleGenerationBatch::STATUS_FAILED => 'Gagal',
            default => $batch->status,
        };

        $html = '<div class="space-y-2 text-sm">';
        $html .= '<div class="flex flex-wrap items-center justify-between gap-2">';
        $html .= '<div class="font-medium">Batch "'.e($batch->keyword).'" &middot; '.e($batch->model ?? '-').'</div>';
        $html .= '<div class="text-gray-500">'.e($status).'</div>';
        $html .= '</div>';
        $html .= '<div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">';
        $html .= '<div class="h-2 rounded-full bg-primary-600" style="width: '.$percent.'%"></div>';
        $html .= '</div>';
        $html .= '<div class="flex flex-wrap gap-4 text-gray-500">';
        $html .= '<span>Progres '.$batch->processed.'/'.$batch->total.'</span>';
        $html .= '<span>AI '.$batch->generated.'</span>';
        $html .= '<span>Template '.$batch->fallback.'</span>';
        $html .= '<span>Gagal '.$batch->failed.'</span>';
        $html .= '<span>Biaya USD '.number_format((float) $batch->actual_cost_usd, 4).'</span>';
        $html .= '</div>';

        if ($batch->error) {
            $html .= '<div class="text-xs text-danger-600">'.e($batch->error).'</div>';
        }

        $html .= '</div>';

        return $html;
    }

    private function aiInfoHtml(Get $get): HtmlString
    {
        $client = app(GeminiClient::class);
        $budget = app(AiBudget::class);
        $estimator = app(AiCostEstimator::class);

        $model = (string) ($get('ai_model') ?: config('ai.model'));
        $count = max(1, (int) ($get('count') ?? 1));
        $count = min($count, max(1, (int) config('ai.budget.max_articles_per_run')));

        $estimated = $estimator->estimateBatch($model, $count);
        $perArticle = $estimator->estimateBatch($model, 1);
        $remaining = $budget->remaining();

        $rows = [
            ['Status API', $client->isConfigured()
                ? 'Aktif — model '.$model
                : 'Belum aktif (AI_API_KEY kosong atau AI_ENABLED=false)'],
            ['Perkiraan biaya batch ('.$count.' artikel)', 'USD '.number_format($estimated, 4)],
            ['Cap bulanan', 'USD '.number_format($budget->monthlyCap(), 2)],
            ['Sisa budget bulan ini', 'USD '.number_format($remaining, 4)],
            ['Perkiraan artikel yang bisa dibuat', $perArticle > 0 ? (string) (int) floor($remaining / $perArticle) : '0'],
        ];

        $html = '<div class="space-y-1 text-sm">';

        foreach ($rows as [$label, $value]) {
            $html .= '<div class="flex justify-between gap-4">';
            $html .= '<span class="text-gray-500">'.e($label).'</span>';
            $html .= '<span class="text-right font-medium">'.e($value).'</span>';
            $html .= '</div>';
        }

        if (! $client->isConfigured()) {
            $html .= '<div class="text-xs text-danger-600">Isi AI_API_KEY di .env server untuk mengaktifkan mode AI.</div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }
}
