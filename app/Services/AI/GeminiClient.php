<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

/**
 * Client tipis untuk Gemini generateContent dengan structured output.
 */
class GeminiClient
{
    /**
     * Panggil Gemini dan kembalikan teks balasan beserta usage token.
     *
     * @param  array<string, mixed>  $schema  responseSchema (subset OpenAPI)
     * @return array{text: string, model: string, prompt_tokens: int, output_tokens: int, cached_tokens: int}
     */
    public function generate(string $system, string $user, array $schema, ?string $model = null): array
    {
        $model = $model ?: (string) config('ai.model');
        $data = $this->request($model, $this->payload($system, $user, $schema));

        $text = '';
        foreach ((array) data_get($data, 'candidates.0.content.parts', []) as $part) {
            $text .= (string) ($part['text'] ?? '');
        }

        if (trim($text) === '') {
            $reason = (string) data_get($data, 'candidates.0.finishReason', 'tidak diketahui');
            $block = (string) data_get($data, 'promptFeedback.blockReason', '');

            throw new AiException('Balasan Gemini kosong (finishReason: '.$reason.($block !== '' ? ', blockReason: '.$block : '').').');
        }

        $usage = (array) ($data['usageMetadata'] ?? []);

        return [
            'text' => $text,
            'model' => $model,
            'prompt_tokens' => (int) ($usage['promptTokenCount'] ?? 0),
            'output_tokens' => (int) ($usage['candidatesTokenCount'] ?? 0),
            'cached_tokens' => (int) ($usage['cachedContentTokenCount'] ?? data_get($usage, 'promptTokensDetails.0.cachedContentTokenCount', 0)),
        ];
    }

    public function isConfigured(): bool
    {
        return (bool) config('ai.enabled')
            && trim((string) config('ai.api_key')) !== ''
            && trim((string) config('ai.model')) !== '';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $model, array $payload): array
    {
        $url = rtrim((string) config('ai.base_url'), '/').'/models/'.$model.':generateContent';
        $maxAttempts = max(1, (int) config('ai.retries') + 1);
        $attempt = 0;
        $lastError = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $response = $this->send($url, $payload);

                if ($response->successful()) {
                    return (array) $response->json();
                }

                $message = $this->errorMessage($response);

                if ($response->status() === 400
                    && str_contains(strtolower($message), 'thinkingconfig')
                    && isset($payload['generationConfig']['thinkingConfig'])) {
                    unset($payload['generationConfig']['thinkingConfig']);
                    $lastError = $message;

                    continue;
                }

                if (in_array($response->status(), [429, 500, 502, 503, 504], true)) {
                    $lastError = 'HTTP '.$response->status().': '.$message;
                    $this->backOff($attempt);

                    continue;
                }

                throw new AiException('Gemini API menolak permintaan (HTTP '.$response->status().'): '.$message);
            } catch (ConnectionException $e) {
                $lastError = $e->getMessage();
                $this->backOff($attempt);
            }
        }

        throw new AiException('Gagal menghubungi Gemini API: '.Str::limit((string) $lastError, 300));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function send(string $url, array $payload): Response
    {
        return Http::withHeaders([
            'x-goog-api-key' => (string) config('ai.api_key'),
            'Content-Type' => 'application/json',
        ])
            ->timeout((int) config('ai.timeout'))
            ->acceptJson()
            ->post($url, $payload);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function payload(string $system, string $user, array $schema): array
    {
        $generationConfig = [
            'temperature' => (float) config('ai.temperature'),
            'topP' => 0.95,
            'maxOutputTokens' => (int) config('ai.max_output_tokens'),
            'responseMimeType' => 'application/json',
            'responseSchema' => $schema,
        ];

        $thinkingBudget = (int) config('ai.thinking_budget');

        if ($thinkingBudget >= 0) {
            $generationConfig['thinkingConfig'] = ['thinkingBudget' => $thinkingBudget];
        }

        return [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $user]],
            ]],
            'generationConfig' => $generationConfig,
        ];
    }

    private function errorMessage(Response $response): string
    {
        $message = (string) data_get((array) $response->json(), 'error.message', '');

        if ($message === '') {
            $message = Str::limit(strip_tags((string) $response->body()), 300);
        }

        return $message !== '' ? $message : 'HTTP '.$response->status();
    }

    private function backOff(int $attempt): void
    {
        $delay = (int) config('ai.retry_delay_ms') * $attempt;

        if ($delay > 0) {
            Sleep::for($delay)->milliseconds();
        }
    }
}
