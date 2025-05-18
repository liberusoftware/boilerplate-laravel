<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Crypt;

class ScreeningDataEncryptor
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response->getContent()) {
            $content = json_decode($response->getContent(), true);

            $fieldsToEncrypt = [
                'background_check_status',
                'credit_report_status',
                'rental_history_status'
            ];

            foreach ($fieldsToEncrypt as $field) {
                if (isset($content[$field])) {
                    $content[$field] = Crypt::encryptString($content[$field]);
                }
            }

            $response->setContent(json_encode($content));
        }

        return $response;
    }
}