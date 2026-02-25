<?php

namespace App\Http\Middleware;

use App\Models\AccessLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessLogMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user) {
            AccessLog::create([
                'user_id' => $user->getKey(),
                'organization_id' => $request->route('case')?->organization_id
                    ?? $request->route('caseFile')?->organization_id
                    ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 2048),
                'action' => sprintf('%s %s', $request->method(), $request->path()),
                'subject_type' => null,
                'subject_id' => null,
                'metadata' => [
                    'status' => $response->getStatusCode(),
                ],
            ]);
        }

        return $response;
    }
}
