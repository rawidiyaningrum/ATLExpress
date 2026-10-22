<?php

namespace App\Services\AI;

use App\Models\Setting;
use App\Services\AiBudget;
use App\Services\AiCostEstimator;
use App\Services\SeoOverrides;
use Illuminate\Support\Str;

/**
 * Menghasilkan satu artikel (isi + metadata SEO) memakai AI.
 */
class AiArticleGenerator
{
    public function __construct(
        private readonly GeminiClient $client,
        private readonly ArticlePrompt $prompt,
        private readonly ArticleBrief $brief,
        private readonly AiArticleNormalizer $normalizer,
        private readonly AiCostEstimator $estimator,
        private readonly AiBudget $budget,
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{article: array<string, mixed>, usage: array<string, mixed>}
     */
    public function generate(
        string $keyword,
        string $categorySlug,
        string $categoryName,
        int $index,
        array $options = [],
        ?string $model = null,
    ): array {
        $model = $model ?: (string) config('ai.model');

        if (! $this->client->isConfigured()) {
            throw new AiException('AI belum dikonfigurasi (AI_API_KEY kosong atau AI_ENABLED=false).');
        }

        if (! $this->budget->canAffordCost($this->estimator->estimateBatch($model, 1))) {
            throw new AiException('Budget AI bulan ini habis atau cap 0, pemanggilan AI diblokir.');
        }

        $brand = $this->brand();
        $brief = $this->brief->forIndex($index);

        $usage = $this->client->generate(
            $this->prompt->system($brand),
            $this->prompt->user($keyword, $categoryName, $brand, $brief),
            $this->prompt->schema(),
            $model,
        );

        $payload = json_decode($usage['text'], true);

        if (! is_array($payload)) {
            throw new AiException('Balasan AI bukan JSON yang valid: '.Str::limit($usage['text'], 200));
        }

        $article = $this->normalizer->normalize($payload);
        $article['category'] = $categorySlug;

        $article = SeoOverrides::apply($article, $options, $keyword, $index);

        return [
            'article' => $article,
            'usage' => $usage,
        ];
    }

    private function brand(): string
    {
        return trim((string) (Setting::getValue('company_name') ?: 'ATL Express'));
    }
}
