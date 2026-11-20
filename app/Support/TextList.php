<?php

namespace App\Support;

class TextList
{
    /**
     * تبدیل یک متن چندخطی به فهرست خطوط تمیز (برای افزودن گروهی نام‌ها یا کارها).
     *
     * @return array<int, string>
     */
    public static function lines(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $items = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // حذف بولت‌های ابتدای خط
            $line = trim((string) preg_replace('/^[\-\*•·]+\s*/u', '', $line));

            // حذف شماره‌گذاری ابتدای خط مثل «1.» «۲)» «3-»
            $line = trim((string) preg_replace('/^(?:\d{1,3}|[\x{06F0}-\x{06F9}]{1,3})[\.\)\-]\s+/u', '', $line));

            if ($line !== '') {
                $items[] = $line;
            }
        }

        return array_values(array_unique($items));
    }
}
