<?php

declare(strict_types=1);

namespace App\Services\Dns;

use App\Contracts\MxResolver;
use Illuminate\Support\Facades\Http;
use Throwable;

final class DohMxResolver implements MxResolver
{
    private const string URL = 'https://cloudflare-dns.com/dns-query';

    public function resolve(string $domain): array
    {
        try {
            $response = Http::acceptJson()->timeout(5)->get(self::URL, ['name' => $domain, 'type' => 'MX']);
            if (! $response->successful()) {
                return [];
            }

            $answers = $response->json('Answer', []);
            if (! is_array($answers)) {
                return [];
            }

            $records = [];
            foreach ($answers as $answer) {
                if (! is_array($answer)) {
                    continue;
                }

                $data = $answer['data'] ?? null;
                if (is_string($data)) {
                    $records[] = trim($data, '"');
                }
            }

            return $records;
        } catch (Throwable) {
            return [];
        }
    }
}
