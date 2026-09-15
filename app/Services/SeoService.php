<?php

namespace App\Services;

use Illuminate\Support\Str;

class SeoService
{
    public static function description(?string $content, int $length = 160): string
    {
        $text = trim(strip_tags((string) $content));
        $text = preg_replace('/\s+/u', ' ', $text);

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, mb_strrpos(mb_substr($text, 0, $length), ' ')) . '...';
    }

    public static function breadcrumb(array $items): array
    {
        $list = [];

        foreach (array_values($items) as $i => [$name, $url]) {
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
            'name' => $settings['company_name'] ?? 'ATL Express',
            'url' => url('/'),
            'logo' => $logo ?? asset('images/logo.png'),
        ];

        if (!empty($sameAs)) {
            $schema['sameAs'] = $sameAs;
        }

        if (!empty($settings['phone'])) {
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
            'name' => $settings['company_name'] ?? 'ATL Express',
            'description' => $settings['about_text'] ?? '',
            'url' => url('/'),
            'logo' => $logo ?? asset('images/logo.png'),
        ];

        if (!empty($settings['phone'])) {
            $schema['telephone'] = $settings['phone'];
        }

        if (!empty($settings['email'])) {
            $schema['email'] = $settings['email'];
        }

        if (!empty($settings['address'])) {
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
}
