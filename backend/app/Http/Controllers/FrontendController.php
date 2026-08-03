<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class FrontendController extends Controller
{
    public function __invoke(): BinaryFileResponse|JsonResponse
    {
        $index = public_path('index.html');
        if (! is_file($index)) {
            return response()->json(['message' => trans('site.frontend_missing')], 503);
        }

        return response()->file($index);
    }
}
