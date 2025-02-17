<?php

namespace App\Http\Middleware;

use App\Helpers\KeyCaseHelper;
use Closure;
use Illuminate\Http\Request;

class ConvertRequestToSnakeCase
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $request->replace(
            resolve(KeyCaseHelper::class)->convert(
                KeyCaseHelper::CASE_SNAKE,
                $request->all()
            )
        );

        return $next($request);
    }
}
