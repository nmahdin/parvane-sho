<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Support\ChallengeExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChallengeExportController extends Controller
{
    /**
     * خروجی اکسل اطلاعات چالش — فقط برای چالش‌های خودِ همین مدیر
     * (قید مالکیت در resolveRouteBinding مدل Challenge اعمال می‌شود).
     */
    public function __invoke(Challenge $challenge): BinaryFileResponse
    {
        return ChallengeExport::download($challenge);
    }
}
