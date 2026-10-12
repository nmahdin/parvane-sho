<?php

namespace App\Support;

/**
 * تبدیل متن ساده مدیر به HTML امن (بدون کتابخانه بیرونی).
 *
 * پشتیبانی می‌کند از: پاراگراف، عنوان با ## و ###، فهرست با - یا * یا شماره،
 * متن پررنگ **متن** و `کد`. همه خروجی‌ها escape می‌شوند.
 */
class RichText
{
    public static function toHtml(?string $text): string
    {
        if ($text === null || trim($text) === '') {
            return '';
        }

        $blocks = preg_split('/\n\s*\n/u', trim(str_replace("\r\n", "\n", $text))) ?: [];
        $html = [];

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            $html[] = static::renderBlock($block);
        }

        return implode("\n", $html);
    }

    private static function renderBlock(string $block): string
    {
        $lines = explode("\n", $block);

        if (preg_match('/^#{2,3}\s+(.+)$/u', $lines[0], $matches) && count($lines) === 1) {
            $level = substr_count(explode(' ', $lines[0])[0], '#') >= 3 ? 'h4' : 'h3';

            return '<'.$level.' class="mt-2 text-base font-semibold text-slate-900">'.static::inline($matches[1]).'</'.$level.'>';
        }

        if (static::everyLineMatches($lines, '/^[-*•]\s+/u')) {
            $items = array_map(
                fn (string $line) => '<li>'.static::inline(preg_replace('/^[-*•]\s+/u', '', trim($line))).'</li>',
                $lines
            );

            return '<ul class="my-3 list-inside list-disc space-y-1.5 text-slate-600">'.implode('', $items).'</ul>';
        }

        if (static::everyLineMatches($lines, '/^\d+[\.\)]\s+/u')) {
            $items = array_map(
                fn (string $line) => '<li>'.static::inline(preg_replace('/^\d+[\.\)]\s+/u', '', trim($line))).'</li>',
                $lines
            );

            return '<ol class="my-3 list-inside list-decimal space-y-1.5 text-slate-600">'.implode('', $items).'</ol>';
        }

        $paragraph = implode('<br>', array_map(fn (string $line) => static::inline(trim($line)), $lines));

        return '<p class="my-3">'.$paragraph.'</p>';
    }

    /**
     * @param  array<int, string>  $lines
     */
    private static function everyLineMatches(array $lines, string $pattern): bool
    {
        foreach ($lines as $line) {
            if (! preg_match($pattern, trim($line))) {
                return false;
            }
        }

        return $lines !== [];
    }

    private static function inline(string $text): string
    {
        $escaped = e($text);

        $escaped = preg_replace('/\*\*(.+?)\*\*/u', '<strong class="font-semibold text-slate-800">$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/`(.+?)`/u', '<code class="rounded bg-slate-100 px-1.5 py-0.5 text-[0.8em]" dir="auto">$1</code>', $escaped) ?? $escaped;

        return $escaped;
    }
}
