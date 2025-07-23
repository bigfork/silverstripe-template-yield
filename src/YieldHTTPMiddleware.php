<?php

namespace Bigfork\SilverstripeTemplateYield;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPStreamResponse;
use SilverStripe\Control\Middleware\HTTPMiddleware;

class YieldHTTPMiddleware implements HTTPMiddleware
{
    public function process(HTTPRequest $request, callable $delegate)
    {
        $response = $delegate($request);

        // Don't intercept files being streamed
        if ($response instanceof HTTPStreamResponse) {
            return $response;
        }

        $body = $response->getBody();
        if ($body) {
            $response->setBody(BlockProvider::yieldIntoString($body));
        }

        return $response;
    }
}
