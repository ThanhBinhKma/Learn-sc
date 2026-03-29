<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureQuizVisitorCookie
{
    public const COOKIE_NAME = 'quiz_visitor_id';

    /** Cookie lifetime: ~400 days (minutes). */
    public const COOKIE_MINUTES = 60 * 24 * 400;

    public function handle(Request $request, Closure $next)
    {
        $id = $request->cookie(self::COOKIE_NAME);

        if (!$this->isValidUuid($id)) {
            $id = (string) Str::uuid();
        }

        $request->attributes->set('quiz_visitor_id', $id);

        /** @var Response $response */
        $response = $next($request);

        return $response->withCookie(cookie(
            self::COOKIE_NAME,
            $id,
            self::COOKIE_MINUTES,
            '/',
            null,
            config('session.secure', false),
            true,
            false,
            'lax'
        ));
    }

    private function isValidUuid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        ) === 1;
    }
}
