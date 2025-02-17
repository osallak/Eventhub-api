<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('auth')->name('auth.')->group(
    function () {
        Route::controller(AuthController::class)->group(
            function () {
                Route::post('/login', 'login');
                Route::post('/register', 'register');
                Route::post('/request-password-reset', 'requestPasswordReset');
                Route::post('/reset-password', 'resetPassword');
                Route::get(
                    '/disconnected', function () {
                        return response()->json(['success' => false, 'errors' => [__('auth.disconnected')]]);
                    }
                );
            }
        );
    }
);

Route::middleware('auth:api')->group(
    function () {
        Route::prefix('auth')->name('auth.')->group(
            function () {
                Route::controller(AuthController::class)->group(
                    function () {
                        Route::post('/me', 'me');
                        Route::post('/logout', 'logout');
                    }
                );
            }
        );
        Route::prefix('users')->name('users.')->group(
            function () {
                Route::controller(UserController::class)->group(
                    function () {
                        Route::post('/', 'createOne');
                        Route::get('/{id}', 'readOne');
                        Route::get('/', 'readAll');
                        Route::put('/{id}', 'updateOne');
                        Route::patch('/{id}', 'patchOne');
                        Route::delete('/{id}', 'deleteOne');
                    }
                );
            }
        );

        Route::prefix('uploads')->name('uploads.')->group(
            function () {
                Route::controller(UploadController::class)->group(
                    function () {
                        Route::post('/', 'createOne');
                        Route::get('/{id}', 'readOne');
                        Route::get('/', 'readAll');
                        Route::post('/{id}', 'updateOne');
                        Route::delete('/{id}', 'deleteOne');
                        Route::delete('/', 'deleteMulti');
                    }
                );
            }
        );
    }
);

Route::get(
    '/hello', function () {
        return response()->json(['success' => true, 'data' => ['message' => 'Hello World!']]);
    }
);

Route::prefix('uploads')->name('uploads.')->group(
    function () {
        Route::controller(UploadController::class)->group(
            function () {
                Route::get('/image/{id}', 'readImage');
            }
        );
    }
);

Route::prefix('cloud')->name('cloud.')->group(
    function () {
        Route::get(
            '/{path}', function () {
                $path = request()->path;
                if (! Storage::disk('cloud')->exists($path)) {
                    return response()->json(
                        [
                            'message' => 'File not found',
                        ], 404
                    );
                }

                return Storage::disk('cloud')->response($path);
            }
        )->where('path', '.*');
    }
);

if (config('app.debug')) {
    Route::prefix('debug')->name('debug.')->group(
        function () {
            // Route that display cache content in json format. Url parameter "cache key" is required (:key).
            Route::get(
                '/cache/{key}', function ($key) {
                    $cacheData = Cache::get($key);
                    $success = $cacheData !== null;

                    return response()->json(
                        [
                            'success' => $success,
                            'data' => $success ? $cacheData : null,
                        ]
                    );
                }
            );
            Route::get(
                '/routes-logs', function () {
                    // Récupérer les logs agrégés par route
                    $routesData = DB::table('routes_logs')
                        ->select('route', DB::raw('SUM(duration) as total_duration'), DB::raw('COUNT(*) as request_count'))
                        ->groupBy('route')
                        ->get();

                    // Calculer le temps total de toutes les requêtes
                    $totalTime = $routesData->sum('total_duration');

                    // Ajouter le pourcentage du total à chaque route
                    $routesData->map(
                        function ($item) use ($totalTime) {
                            $item->total_percentage = $totalTime > 0 ? ($item->total_duration / $totalTime) * 100 : 0;

                            return $item;
                        }
                    );

                    // Retourner les données
                    return response()->json(
                        [
                            'routes' => $routesData,
                            'total_time_ms' => $totalTime,
                        ]
                    );
                }
            );
        }
    );
}
