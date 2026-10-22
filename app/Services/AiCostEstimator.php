<?php

namespace App\Services;

/**
 * Menghitung perkiraan biaya pemakaian API AI dari jumlah token.
 */
class AiCostEstimator
{
    /**
     * @return array{in: float, out: float}
     */
    public function price(string $model): array
    {
        $prices = (array) config('ai.prices', []);
        $price = $prices[$model] ?? config('ai.default_price');

        return [
            'in' => (float) ($price['in'] ?? 0),
            'out' => (float) ($price['out'] ?? 0),
        ];
    }

    public function cost(string $model, int $promptTokens, int $outputTokens): float
    {
        $price = $this->price($model);

        return round(
            ($promptTokens / 1_000_000) * $price['in']
            + ($outputTokens / 1_000_000) * $price['out'],
            6
        );
    }

    /**
     * Perkiraan biaya satu batch artikel.
     */
    public function estimateBatch(string $model, int $articles, int $promptTokensPerArticle = 1100, int $outputTokensPerArticle = 1800): float
    {
        $articles = max(0, $articles);

        return $this->cost(
            $model,
            $promptTokensPerArticle * $articles,
            $outputTokensPerArticle * $articles,
        );
    }
}
