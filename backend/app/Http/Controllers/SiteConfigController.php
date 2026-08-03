<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SiteConfiguration;
use Illuminate\Http\JsonResponse;

final class SiteConfigController extends Controller
{
    public function __invoke(SiteConfiguration $configuration): JsonResponse
    {
        return response()->json($configuration->publicData());
    }
}
