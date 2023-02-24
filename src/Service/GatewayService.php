<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ForwardService
 * @package App\Service
 */
class GatewayService
{
    public function forward(Request $request): Response
    {
        print_r(
            'forward request to ' . $request->attributes->get('route-to') . $request->getRequestUri() . ' via CURL'
        );exit;
    }
}
