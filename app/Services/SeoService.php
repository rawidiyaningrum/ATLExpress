<?php

namespace App\Services;

class SeoService
{
    public static function organizationId(): string
    {
        return url('/').'#organization';
    }

    public static function description(?string $content, int $length = 160): string
    {
        $text = strip_tags((string) $content);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $cut = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($cut, ' ');

        // Tanpa spasi di dalam potongan (mis. satu kata panjang / teks tanpa
        // spasi), mb_strrpos mengembalikan false. Jangan sampai deskripsi
        // berubah menjadi hanya "...": potong langsung di batas panjang.
        if ($lastSpace === false) {
            return rtrim($cut).'...';
        }

        return rtrim(mb_substr($cut, 0, $lastSpace)).'...';
    }

    /**
     * Potong judul di batas kata agar tidak melebihi batas tampil di SERP.
     */
    public static function title(?string $title, int $length = 60): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $title));

        if ($text === '' || mb_strlen($text) <= $length) {
            return $text;
        }

        $limit = max(1, $length - 1);
        $cut = mb_substr($text, 0, $limit);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace > 0) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " \t\n\r\0\x0B.,;:-").'…';
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $items  Pasangan [url, name].
     */
    public static function breadcrumb(array $items): array
    {
        $list = [];

        foreach (array_values($items) as $i => [$url, $name]) {
            $item = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $name,
            ];

            if ($url) {
                $item['item'] = $url;
            }

            $list[] = $item;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    public static function organization(array $settings, ?string $logo = null): array
    {
        $sameAs = array_values(array_filter([
            $settings['facebook'] ?? null,
            $settings['instagram'] ?? null,
            $settings['youtube'] ?? null,
        ]));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => self::organizationId(),
            'name' => $settings['company_name'] ?? 'ATL Express',
            'url' => url('/'),
            'logo' => $logo ?? asset('images/logo.png'),
        ];

        if (! empty($sameAs)) {
            $schema['sameAs'] = $sameAs;
        }

        if (! empty($settings['phone'])) {
            $schema['contactPoint'] = [
                '@type' => 'ContactPoint',
                'telephone' => $settings['phone'],
                'contactType' => 'customer service',
                'areaServed' => 'ID',
                'availableLanguage' => 'Indonesian',
            ];
        }

        return $schema;
    }

    public static function localBusiness(array $settings, ?string $logo = null): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => url('/').'#localbusiness',
            'name' => $settings['company_name'] ?? 'ATL Express',
            'description' => $settings['about_text'] ?? '',
            'url' => url('/'),
            'logo' => $logo ?? asset('images/logo.png'),
            'image' => $logo ?? asset('images/og-default.png'),
            'parentOrganization' => ['@id' => self::organizationId()],
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'Indonesia',
            ],
        ];

        if (! empty($settings['phone'])) {
            $schema['telephone'] = $settings['phone'];
        }

        if (! empty($settings['email'])) {
            $schema['email'] = $settings['email'];
        }

        if (! empty($settings['address'])) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings['address'],
                'addressCountry' => 'ID',
            ];
        }

        return $schema;
    }

    public static function webPage(
        string $title,
        string $description,
        string $url,
        ?string $image = null,
        string $type = 'WebPage'
    ): array {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $title,
            'description' => $description,
            'url' => $url,
        ];

        if ($image) {
            $schema['image'] = $image;
        }

        return $schema;
    }

    /**
     * @param  array<int, array{q: string, a: string}>  $items
     */
    public static function faqPage(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn (array $item): array => [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['a'],
                ],
            ], array_values($items)),
        ];
    }
}
