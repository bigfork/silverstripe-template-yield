<?php

namespace Bigfork\SilverstripeTemplateYield;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPMiddleware;

class YieldHTTPMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        $response = $delegate($request);
        $body = $response->getBody();

        if ($body) {
            $response->setBody(BlockProvider::yieldIntoString($body));
        }

        return $response;
    }
}
