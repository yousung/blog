<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ObfuscateId
{
    public function handle(Request $request, Closure $next)
    {
        if ($value = $request->route()->parameter('post')) {
            $post = \App\Post::findOrFail(optimus()->decode($value));
            $request->route()->setParameter('post', $post);
        }

        return $next($request);
    }
}
