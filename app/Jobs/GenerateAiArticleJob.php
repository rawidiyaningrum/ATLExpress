<?php

namespace App\Jobs;

use App\Models\AiUsage;
use App\Models\ArticleGenerationBatch;
use App\Services\AI\AiArticleGenerator;
use App\Services\AI\AiException;
use App\Services\AiCostEstimator;
use App\Services\ArticleTemplateGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Membuat satu artikel lewat AI. Satu job per artikel supaya permintaan
 * tidak memblokir halaman admin dan rate limit API tetap terkendali.
 */
class GenerateAiArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public readonly string $batchId,
        public readonly string $keyword,
        public readonly string $categorySlug,
        public readonly int $index,
        public readonly array $options = [],
        public readonly ?string $model = null,
        public readonly bool $allowFallback = true,
    ) {
    }

    public function handle(
        AiArticleGenerator $generator,
        AiCostEstimator $estimator,
        ArticleTemplateGenerator $templates,
    ): void {
        $batch = ArticleGenerationBatch::query()->where('uuid', $this->batchId)->first();

        if ($batch === null) {
            return;
        }

        $model = $this->model ?: (string) config('ai.model');

        $batch->update(['status' => ArticleGenerationBatch::STATUS_RUNNING]);

        try {
            $result = $generator->generate(
                $this->keyword,
                $this->categorySlug,
                $templates->categoryName($this->categorySlug),
                $this->index,
                $this->options,
                $model,
            );

            $article = $result['article'];
            $article['slug'] = $templates->uniqueSlugFor((string) $article['title']);

            $templates->publishArticle($article, $this->publishedAt());

            $this->logUsage($model, $result['usage'], 'success');

            $batch->increment('generated');
        } catch (AiException $e) {
            $this->handleFailure($templates, $batch, $model, $e);
        } catch (Throwable $e) {
            $this->handleFailure($templates, $batch, $model, $e);
        } finally {
            $this->syncBatch($batch);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $batch = ArticleGenerationBatch::query()->where('uuid', $this->batchId)->first();

        if ($batch === null) {
            return;
        }

        $batch->increment('failed');

        $this->logUsage(
            $this->model ?: (string) config('ai.model'),
            null,
            'failed',
            $exception?->getMessage() ?? 'Job gagal tanpa pesan.'
        );

        $this->syncBatch($batch);
    }

    private function handleFailure(
        ArticleTemplateGenerator $templates,
        ArticleGenerationBatch $batch,
        string $model,
        Throwable $e,
    ): void {
        $this->logUsage($model, null, 'failed', $e->getMessage());

        Log::warning('GenerateAiArticleJob gagal.', [
            'batch' => $this->batchId,
            'index' => $this->index,
            'error' => $e->getMessage(),
        ]);

        if (! $this->allowFallback) {
            $batch->increment('failed');

            return;
        }

        try {
            $article = $templates->fallbackArticle($this->keyword, $this->categorySlug, $this->index);
            $templates->publishArticle($article, $this->publishedAt());

            $batch->increment('fallback');
        } catch (Throwable $fallbackError) {
            Log::error('Fallback artikel template gagal.', [
                'batch' => $this->batchId,
                'index' => $this->index,
                'error' => $fallbackError->getMessage(),
            ]);

            $batch->increment('failed');
        }
    }

    private function syncBatch(ArticleGenerationBatch $batch): void
    {
        $batch->refresh();

        $batch->processed = $batch->generated + $batch->fallback + $batch->failed;
        $batch->actual_cost_usd = (float) AiUsage::query()
            ->where('batch_id', $batch->uuid)
            ->sum('estimated_cost_usd');

        $batch->status = $batch->processed >= $batch->total
            ? (($batch->generated + $batch->fallback) > 0
                ? ArticleGenerationBatch::STATUS_COMPLETED
                : ArticleGenerationBatch::STATUS_FAILED)
            : ArticleGenerationBatch::STATUS_RUNNING;

        $batch->save();
    }

    /**
     * @param  array<string, mixed>|null  $usage
     */
    private function logUsage(string $model, ?array $usage, string $status, ?string $error = null): void
    {
        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $outputTokens = (int) ($usage['output_tokens'] ?? 0);

        AiUsage::create([
            'provider' => (string) config('ai.provider', 'gemini'),
            'model' => $model,
            'purpose' => 'article',
            'batch_id' => $this->batchId,
            'prompt_tokens' => $promptTokens,
            'output_tokens' => $outputTokens,
            'cached_tokens' => (int) ($usage['cached_tokens'] ?? 0),
            'estimated_cost_usd' => app(AiCostEstimator::class)->cost($model, $promptTokens, $outputTokens),
            'status' => $status,
            'error' => $error !== null ? Str::limit($error, 500) : null,
        ]);
    }

    private function publishedAt(): ?Carbon
    {
        $value = $this->options['published_at'] ?? null;

        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value);
        }

        return null;
    }
}
