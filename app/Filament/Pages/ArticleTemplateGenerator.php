<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\OnlyAdmins;
use App\Filament\Resources\PostResource;
use App\Models\Category;
use App\Services\ArticleTemplateGenerator as ArticleTemplateGeneratorService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ArticleTemplateGenerator extends Page
{
    use OnlyAdmins;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Management';

    protected static ?string $navigationLabel = 'Generator Artikel';

    protected static ?string $title = 'Generator Artikel (Template)';

    protected static ?string $slug = 'generator-artikel';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.article-template-generator';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'category_slug' => 'berita',
            'count' => 1,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Generate Artikel Template-Based')
                    ->description('Buat artikel blog langsung ter-publish berdasarkan keyword tanpa AI.')
                    ->schema([
                        Forms\Components\TextInput::make('keyword')
                            ->label('Keyword')
                            ->placeholder('Contoh: ekspedisi kargo jakarta')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Masukkan satu keyword utama untuk topik artikel.'),
                        Forms\Components\Select::make('category_slug')
                            ->label('Kategori')
                            ->options(fn () => Category::query()->pluck('name', 'slug')->all())
                            ->required()
                            ->searchable(),
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
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate & Publish')
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

        $result = app(ArticleTemplateGeneratorService::class)->generate($keyword, $categorySlug, $count, [
            'published_at' => $publishedAt,
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
}
