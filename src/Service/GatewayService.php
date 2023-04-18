<?php

namespace App\Service;

use Google\Cloud\Logging\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ForwardService
 * @package App\Service
 */
class GatewayService
{
    /** @var LoggingService  */
    protected LoggingService $loggingService;

    /**
     * @param LoggingService $loggingService
     */
    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function forward(Request $request): Response
    {
        $endpoint = $request->attributes->get('route-to') . $request->getRequestUri();

        /**
         * TODO: move logging to middleware.
         */
        $this->loggingService->log(sprintf("Forwarding request to %s%s to destination %s via CURL",
            $request->attributes->get('route-to'),
            $request->getRequestUri(),
            $request->attributes->get('route-name')
        ), [
            'destination' => $request->attributes->get('route-name'),
            'endpoint' => $endpoint
        ], $request, null, Logger::INFO);

        $headers = [];

        foreach($request->headers->all() as $header => $item ) {
            $headers[] = sprintf("%s: %s", $header, $item[0]);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_URL, $endpoint);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $responseHeaders = [];

        /**
         * get response headers from destination to flow back to response.
         */
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders) {
            $length = strlen($header);

            $header = explode(':', $header, 2);

            if(
                sizeof($header) < 2 ||
                in_array($header[0], ['Server', 'Transfer-Encoding', 'Vary', 'Date'])
            ) {
                return $length;

            }

            $responseHeaders[trim($header[0])] = trim($header[1]);

            return $length;
        });

        $result = (curl_exec($ch));
        $errorCode = curl_errno($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);

        switch($errorCode) {
            case CURLE_OK:
                break;

            case CURLE_OPERATION_TIMEOUTED:
                throw new \Exception('Connection to service timed out');

            default:
                /**
                 * TODO: log these errors with curl error code.
                 */
                throw new \Exception('An unknown error occurred');
        }

        switch($info['http_code']){
            case Response::HTTP_OK:
            case Response::HTTP_ACCEPTED:
            case Response::HTTP_NO_CONTENT:
                break;

            /**
             * TODO: add errors for non-success response codes.
             */

            default:
                $result = null;
        }

        return new Response(
            $result,
            ($result == null) ? Response::HTTP_NO_CONTENT : $info['http_code'],
            $responseHeaders
        );
    }
}
