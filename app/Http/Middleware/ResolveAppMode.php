<?php

namespace App\Http\Middleware;

use App\Services\AppModeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ResolveAppMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $modeService = app(AppModeService::class);
        $state = $modeService->resolve();

        $connection = $state['mode'] === AppModeService::MODE_CLOUD ? 'pgsql' : 'sqlite';

        config([
            'database.default' => $connection,
            'queue.batching.database' => $connection,
            'queue.failed.database' => $connection,
        ]);

        DB::purge($connection);
        DB::reconnect($connection);

        return $next($request);
    }
}
