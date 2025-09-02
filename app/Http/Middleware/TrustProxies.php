<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies as Base;

class TrustProxies extends Base
{
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR
      | Request::HEADER_X_FORWARDED_HOST
      | Request::HEADER_X_FORWARDED_PORT
      | Request::HEADER_X_FORWARDED_PROTO;

    protected function proxies(): array|string|null
    {
        return config('edge.proxies', []);
    }
}
