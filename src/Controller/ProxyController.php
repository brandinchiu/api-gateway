<?php

namespace App\Controller;

use App\Service\GatewayService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProxyController
 * @package App\Controller
 */
class ProxyController extends AbstractController
{
    /** @var GatewayService  */
    protected GatewayService $gatewayService;

    /**
     * @param GatewayService $gatewayService
     */
    public function __construct(GatewayService $gatewayService)
    {
        $this->gatewayService = $gatewayService;
    }

    public function route(Request $request): Response
    {
        /**
         * push request through curl service.
         */
        return $this->gatewayService->forward($request);
    }
}
