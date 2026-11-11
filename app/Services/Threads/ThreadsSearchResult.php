<?php

namespace App\Services\Threads;

use Illuminate\Support\Carbon;

/**
 * Satu postingan Threads yang sudah dinormalisasi dari sumber mana pun
 * (API resmi ataupun hasil scraping).
 */
final class ThreadsSearchResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly string $threadsId,
        public readonly ?string $username = null,
        public readonly ?string $text = null,
        public readonly ?string $permalink = null,
        public readonly ?string $mediaType = null,
        public readonly ?Carbon $postedAt = null,
        public readonly bool $hasReplies = false,
        public readonly bool $isReply = false,
        public readonly bool $isQuotePost = false,
        public readonly array $raw = [],
    ) {}

    /**
     * Susun dari payload mentah API/scraper. Menerima beberapa variasi nama
     * field karena struktur Threads/Instagram memakai penamaan berbeda
     * (mis. caption.text untuk hasil scraping).
     *
     * @param  array<string, mixed>  $item
     */
    public static function fromArray(array $item): ?self
    {
        $id = (string) ($item['id'] ?? $item['pk'] ?? $item['code'] ?? '');

        if ($id === '') {
            return null;
        }

        $text = $item['text']
            ?? $item['caption']['text']
            ?? $item['caption']
            ?? null;

        if (is_array($text)) {
            $text = $text['text'] ?? null;
        }

        $username = $item['username']
            ?? $item['user']['username']
            ?? null;

        $timestamp = $item['timestamp']
            ?? $item['taken_at']
            ?? $item['created_at']
            ?? null;

        $permalink = $item['permalink'] ?? null;

        if ($permalink === null && isset($item['code'])) {
            $user = $username ? '@'.$username : 'post';
            $permalink = 'https://www.threads.com/'.$user.'/post/'.$item['code'];
        }

        return new self(
            threadsId: $id,
            username: $username !== null ? (string) $username : null,
            text: $text !== null ? (string) $text : null,
            permalink: $permalink !== null ? (string) $permalink : null,
            mediaType: isset($item['media_type']) ? (string) $item['media_type'] : null,
            postedAt: self::parseTimestamp($timestamp),
            hasReplies: (bool) ($item['has_replies'] ?? false),
            isReply: (bool) ($item['is_reply'] ?? false),
            isQuotePost: (bool) ($item['is_quote_post'] ?? false),
            raw: $item,
        );
    }

    private static function parseTimestamp(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::createFromTimestamp((int) $value);
            }

            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
