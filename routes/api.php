<?php

use App\Domain\Enums\MessageCode;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => [
        'auth.basic',
    ],
], static function () {
    Route::prefix('v1')->group(base_path('routes/api_v1.php'));
    Route::get('enums', static function () {
        return response()->json([
            'MessageCodes' => MessageCode::asIdTitles(),
        ]);
    });
});
