<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ViewCompileTest extends TestCase
{
    /**
     * همه ویوها باید تمیز کامپایل شوند.
     *
     * نکته مهم: در Blade اگر در فایلی `@php(...)` (شکل یک‌خطی) قبل از یک بلوک
     * `@php ... @endphp` بیاید، کامپایلر از آن `@php` تا اولین `@endphp` را یک
     * بلوک PHP در نظر می‌گیرد و بقیه دایرکتیوهای وسط فایل خام می‌مانند؛ نتیجه‌اش
     * خطای Parse و یا صفحه سفید است. این تست همان اشتباه را می‌گیرد.
     */
    public function test_every_blade_view_compiles_without_leftover_directives(): void
    {
        $compiler = app('blade.compiler');
        $broken = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $compiled = (string) $compiler->compileString(File::get($file->getPathname()));

            if (preg_match('/(?<!@)@(section|endsection|if|endif|unless|endunless|foreach|endforeach|forelse|empty|endforelse|php|endphp|csrf|method|checked|push|endpush)\b/', $compiled)) {
                $broken[] = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertSame([], $broken, 'این ویوها کامپایل نشده‌اند (خام‌ماندن دایرکتیوها): '.implode(', ', $broken));
    }

    /**
     * دایرکتیوهای پرکاربرد هم باید درست ترجمه شوند.
     */
    public function test_blade_directives_are_translated(): void
    {
        $compiler = app('blade.compiler');

        $compiled = $compiler->compileString('@if ($a)
{{ $a }}
@endif');

        $this->assertStringContainsString('if($a):', $compiled);
        $this->assertStringContainsString('echo e($a);', $compiled);
        $this->assertStringContainsString('endif;', $compiled);
        $this->assertStringNotContainsString('@if', $compiled);
    }

    /**
     * ویوهای اصلی باید وجود داشته باشند، وگرنه تست کامپایل بی‌معنا است.
     */
    public function test_main_views_exist(): void
    {
        foreach ([
            'layouts/public.blade.php',
            'layouts/panel.blade.php',
            'challenges/show.blade.php',
            'participant/show.blade.php',
            'panel/challenges/show.blade.php',
        ] as $view) {
            $this->assertTrue(File::exists(resource_path('views/'.$view)), "ویو {$view} پیدا نشد.");
        }
    }
}
