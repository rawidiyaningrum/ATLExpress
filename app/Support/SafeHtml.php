<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitasi HTML dari RichEditor / hasil AI sebelum dirender dengan {!! !!}.
 * Whitelist tag & skema URL agar script/atribut berbahaya tidak lolos.
 */
class SafeHtml
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function clean(?string $html): string
    {
        $html = (string) $html;

        if (trim($html) === '') {
            return '';
        }

        return self::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return self::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig())
                ->allowSafeElements()
                ->allowElement('img', ['src', 'alt', 'title', 'width', 'height'])
                ->allowElement('figure')
                ->allowElement('figcaption')
                ->allowAttribute('class', ['p', 'span', 'a', 'img', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'figure'])
                ->allowAttribute('target', ['a'])
                ->allowAttribute('rel', ['a'])
                ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
                ->allowMediaSchemes(['http', 'https'])
                ->forceHttpsUrls()
        );
    }
}
