<?php

namespace App\Http\Middleware;

use Closure;

class ChangSlugParamMiddleWare
{

    private $params = ['tag'];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->method() === 'GET'){
            foreach($this->params as $param){
                $this->isSlugParam($request, $param);
            }
        }

        //  todo : dev 개발 
        // dd($request->all());

        return $next($request);
    }

    private function isSlugParam($request, $param)
    {
        if($val = $request->input($param)){
            $slug = str_slug($val, '-');
        }
    }
}
