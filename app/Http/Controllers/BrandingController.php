<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * سرو لوگو و فاوآیکون آپلودشده از پنل (فایل‌ها در دیسک local و پوشه
 * branding نگهداری می‌شوند و از این مسیر عمومی خوانده می‌شوند).
 */
class BrandingController extends Controller
{
    public function show(Request $request, string $kind): BinaryFileResponse
    {
        abort_unless(in_array($kind, ['logo', 'favicon'], true), 404);

        $file = Setting::get('site_'.$kind);

        abort_unless(
            is_string($file) && $file !== '' && Storage::disk('local')->exists('branding/'.$file),
            404
        );

        return response()
            ->file(Storage::disk('local')->path('branding/'.$file))
            ->setPublic()
            ->setMaxAge(3600);
    }
}
