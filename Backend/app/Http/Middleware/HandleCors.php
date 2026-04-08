<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleCors
{
    public function handle(Request $request, Closure $next)
    {
        // Headers CORS padrão
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '3600',
        ];

        // Se for uma requisição OPTIONS (preflight), responder imediatamente
        if ($request->isMethod('OPTIONS')) {
            return response('', 200, $headers);
        }

        // Adicionar headers na resposta
        $response = $next($request);
        
        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }
}
