<?php

namespace App\Console\Commands;

use App\Services\AI\AiArticleGenerator;
use App\Services\AI\GeminiClient;
use App\Services\AiCostEstimator;
use Illuminate\Console\Command;

class AiTestCommand extends Command
{
    protected $signature = 'ai:test {--model=} {--keyword=jasa cargoakarta}';

    protected $description = 'Uji koneksi API AI dan generate satu artikel contoh';

    public function handle(AiArticleGenerator $generator, GeminiClient $client, AiCostEstimator $estimator): int
    {
        if (! $client->isConfigured()) {
            $this->error('AI belum dikonfigurasi. Isi AI_API_KEY di .env dan pastikan AI_ENABLED=true.');

            return self::FAILURE;
        }

        $model = (string) ($this->option('model') ?: config('ai.model'));
        $keyword = (string) $this->option('keyword');

        $this->info("Menguji model {$model} dengan keyword \"{$keyword}\" ...");

        try {
            $result = $generator->generate($keyword, 'berita', 'Berita', 1, [], $model);
        } catch (\Throwable $e) {
            $this->error('Gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $article = $result['article'];
        $usage = $result['usage'];
        $cost = $estimator->cost($model, (int) $usage['prompt_tokens'], (int) $usage['output_tokens']);

        $this->newLine();
        $this->info('Judul       : '.$article['title']);
        $this->info('Meta title  : '.$article['meta_title']);
        $this->info('Meta desc   : '.$article['meta_description']);
        $this->info('Meta keyword: '.$article['meta_keywords']);
        $this->info('Lead        : '.mb_substr((string) $article['lead'], 0, 160).'...');
        $this->info('Bagian      : '.count($article['sections']).' section, '.count($article['tips']).' tips');
        $this->newLine();
        $this->info('Token input : '.$usage['prompt_tokens']);
        $this->info('Token output: '.$usage['output_tokens']);
        $this->info('Biaya       : USD '.number_format($cost, 6));

        return self::SUCCESS;
    }
}
