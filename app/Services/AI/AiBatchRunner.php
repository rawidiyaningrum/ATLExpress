<?php

namespace App\Services\AI;

use App\Jobs\GenerateAiArticleJob;
use App\Models\ArticleGenerationBatch;
use App\Services\AiBudget;
use App\Services\AiCostEstimator;

/**
 * Menyusun batch generate artikel AI lalu menitipkan jobnya ke queue.
 */
class AiBatchRunner
{
    public function __construct(
        private readonly AiCostEstimator $estimator,
        private readonly AiBudget $budget,
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{batch: ArticleGenerationBatch, estimated_cost: float, allowed: bool, reason: string, count: int}
     */
    public function dispatch(
        string $keyword,
        string $categorySlug,
        int $count,
        array $options = [],
        ?string $model = null,
        bool $allowFallback = true,
    ): array {
        $model = $model ?: (string) config('ai.model');
        $max = max(1, (int) config('ai.budget.max_articles_per_run'));
        $count = max(1, min($max, $count));

        $estimated = $this->estimator->estimateBatch($model, $count);
        $check = $this->budget->check($estimated);

        $batch = ArticleGenerationBatch::create([
            'keyword' => $keyword,
            'category_slug' => $categorySlug,
            'method' => 'ai',
            'model' => $model,
            'status' => ArticleGenerationBatch::STATUS_QUEUED,
            'total' => $count,
            'estimated_cost_usd' => $estimated,
            'options' => $options,
        ]);

        if (! $check['allowed']) {
            $batch->update([
                'status' => ArticleGenerationBatch::STATUS_FAILED,
                'error' => $check['reason'],
            ]);

            return [
                'batch' => $batch,
                'estimated_cost' => $estimated,
                'allowed' => false,
                'reason' => $check['reason'],
                'count' => $count,
            ];
        }

        for ($index = 1; $index <= $count; $index++) {
            GenerateAiArticleJob::dispatch(
                $batch->uuid,
                $keyword,
                $categorySlug,
                $index,
                $options,
                $model,
                $allowFallback,
            );
        }

        return [
            'batch' => $batch,
            'estimated_cost' => $estimated,
            'allowed' => true,
            'reason' => '',
            'count' => $count,
        ];
    }
}
