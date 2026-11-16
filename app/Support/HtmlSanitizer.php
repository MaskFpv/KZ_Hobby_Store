<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Bersihkan HTML dari editor artikel sebelum disimpan: hanya tag & atribut format yang aman.
 * Script, event handler (onclick, dst.), style liar, dan iframe selain YouTube dibuang.
 */
class HtmlSanitizer
{
    public static function clean(?string $html): string
    {
        static $purifier = null;

        if (! $purifier) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('Cache.SerializerPath', self::cacheDir());
            $config->set('HTML.Allowed', implode(',', [
                'p', 'br', 'hr', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'mark', 'code', 'pre',
                'h2', 'h3', 'h4', 'blockquote', 'ul', 'ol', 'li',
                'a[href|title|target]', 'img[src|alt|width|height]', 'figure', 'figcaption',
                'table', 'thead', 'tbody', 'tr', 'th[colspan|rowspan]', 'td[colspan|rowspan]',
                'iframe[src|width|height|title|allowfullscreen|frameborder]',
                'span[style]', 'p[style]', 'h2[style]', 'h3[style]',
            ]));
            $config->set('CSS.AllowedProperties', ['text-align', 'color', 'background-color']);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '%^https://(www\.youtube(-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');
            $config->set('HTML.DefinitionID', 'kz-article');
            $config->set('HTML.DefinitionRev', 1);
            if ($def = $config->maybeGetRawHTMLDefinition()) {
                $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
                $def->addElement('figcaption', 'Inline', 'Flow', 'Common');
                $def->addElement('mark', 'Inline', 'Inline', 'Common');
                $def->addAttribute('iframe', 'allowfullscreen', 'Bool');
            }
            $purifier = new HTMLPurifier($config);
        }

        return trim($purifier->purify((string) $html));
    }

    private static function cacheDir(): string
    {
        $dir = storage_path('framework/cache/purifier');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}
