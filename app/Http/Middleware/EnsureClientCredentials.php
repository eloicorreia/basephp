<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureClientCredentials
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = auth()->guard('api');

        if ($guard->user() !== null) {
            throw new AuthorizationException(
                'Acesso permitido apenas para tokens client credentials.'
            );
        }

        if (! method_exists($guard, 'client')) {
            throw new AuthenticationException();
        }

        $client = $guard->client();

        if ($client === null) {
            throw new AuthenticationException();
        }

        if (! method_exists($client, 'hasGrantType') || ! $client->hasGrantType('client_credentials')) {
            throw new AuthorizationException(
                'O client OAuth informado não possui grant client credentials.'
            );
        }

        $request->attributes->set('oauth_client_id', $client->getKey());

        return $next($request);
    }
}
