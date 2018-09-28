<?php


namespace App\Http\Middleware;

use Closure;
use App\Facades\Logger;

class RouterLogger
{

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$response = $next($request);
		Logger::logWebActivity($request);

		return $response;
	}


}