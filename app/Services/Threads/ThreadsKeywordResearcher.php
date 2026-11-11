<?php

namespace App\Services\Threads;

use App\Models\AiUsage;
use App\Services\AI\AiException;
use App\Services\AI\GeminiClient;
use App\Services\AiBudget;
use App\Services\AiCostEstimator;
use Illuminate\Support\Str;
use Throwable;

/**
 * Menghasilkan kandidat keyword permintaan kargo di Threads memakai Gemini
 * yang sudah terpasang. Hasilnya hanya usulan — admin yang memutuskan mana
 * yang diaktifkan.
 */
class ThreadsKeywordResearcher
{
    public function __construct(
        private readonly GeminiClient $client,
        private readonly AiBudget $budget,
        private readonly AiCostEstimator $estimator,
    ) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * @return array<int, array{keyword: string, mode: string}>
     *
     * @throws AiException
     */
    public function suggest(string $seed = '', int $count = 15, ?string $model = null): array
    {
        if (! $this->isConfigured()) {
            throw new AiException('AI belum dikonfigurasi. Isi AI_API_KEY di .env server dan pastikan AI_ENABLED=true.');
        }

        $model = $model ?: (string) config('ai.model');
        $count = max(5, min($count, 40));

        // Estimat: prompt singkat + output daftar keyword.
        $estimated = $this->estimator->estimateBatch($model, 1, 400, 600);

        $check = $this->budget->check($estimated);

        if (! $check['allowed']) {
            throw new AiException($check['reason']);
        }

        try {
            $result = $this->client->generate(
                $this->systemPrompt(),
                $this->userPrompt($seed, $count),
                $this->schema($count),
                $model,
            );
        } catch (AiException $e) {
            $this->logUsage($model, null, 'failed', $e->getMessage());

            throw $e;
        } catch (Throwable $e) {
            $this->logUsage($model, null, 'failed', $e->getMessage());

            throw new AiException('Gagal membuat saran keyword: '.$e->getMessage(), 0, $e);
        }

        $this->logUsage($model, $result, 'success');

        return $this->parse($result['text'] ?? '');
    }

    /**
     * @return array<int, array{keyword: string, mode: string}>
     */
    private function parse(string $text): array
    {
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            return [];
        }

        $keywords = $decoded['keywords'] ?? [];

        if (! is_array($keywords)) {
            return [];
        }

        $out = [];
        $seen = [];

        foreach ($keywords as $item) {
            $keyword = is_array($item) ? trim((string) ($item['keyword'] ?? '')) : trim((string) $item);

            if ($keyword === '') {
                continue;
            }

            $lower = Str::lower($keyword);

            if (isset($seen[$lower])) {
                continue;
            }

            $seen[$lower] = true;

            $mode = is_array($item) ? (string) ($item['mode'] ?? 'keyword') : 'keyword';
            $mode = $mode === 'tag' ? 'tag' : 'keyword';

            // Normalisasi tag: hapus tanda '#' di depan.
            $keyword = ltrim($keyword, '#');

            $out[] = ['keyword' => $keyword, 'mode' => $mode];
        }

        return $out;
    }

    private function systemPrompt(): string
    {
        return 'Kamu analis keyword untuk perusahaan kargo/ekspedisi Indonesia (ATL Express). '
            .'Tugasmu menghasilkan kata kunci yang kemungkinan besar muncul di postingan Threads '
            .'dari orang yang SEDANG MENCARI jasa pengiriman barang, ekspedisi, atau pindahan. '
            .'Fokus pada niat mencari (butuh/cari/rekomendasi), layanan (cargo, ekspedisi, pindahan), '
            .'jenis barang (motor, mobil, furniture), dan kota tujuan populer di Indonesia. '
            .'Gunakan bahasa sehari-hari orang Indonesia. Jangan buat keyword yang ambigu atau terlalu umum.';
    }

    private function userPrompt(string $seed, int $count): string
    {
        $seed = trim($seed);

        $prompt = 'Buat tepat '.$count.' kandidat keyword pencarian Threads. '
            .'Balas JSON sesuai skema. Setiap item punya "keyword" (huruf kecil, tanpa tanda #) '
            .'dan "mode" bernilai "keyword" untuk frasa pencarian atau "tag" untuk topic tag populer.';

        if ($seed !== '') {
            $prompt .= ' Fokuskan sebagian besar kandidat di sekitar tema: "'.$seed.'".';
        }

        return $prompt;
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(int $count): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'keywords' => [
                    'type' => 'array',
                    'description' => 'Daftar kandidat keyword.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'keyword' => ['type' => 'string'],
                            'mode' => ['type' => 'string', 'enum' => ['keyword', 'tag']],
                        ],
                        'required' => ['keyword', 'mode'],
                    ],
                    'minItems' => $count,
                    'maxItems' => $count,
                ],
            ],
            'required' => ['keywords'],
        ];
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
            'purpose' => 'threads_keywords',
            'prompt_tokens' => $promptTokens,
            'output_tokens' => $outputTokens,
            'cached_tokens' => (int) ($usage['cached_tokens'] ?? 0),
            'estimated_cost_usd' => $this->estimator->cost($model, $promptTokens, $outputTokens),
            'status' => $status,
            'error' => $error !== null ? Str::limit($error, 500) : null,
        ]);
    }
}
