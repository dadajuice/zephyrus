<?php namespace Zephyrus\Network\HttpRequester\RequestTypes;

use Zephyrus\Exceptions\HttpRequesterException;

trait HttpStreamRequest
{
    /**
     * Executes an HTTP request that returns some sort of stream (e.g. SSE). Will execute the given callback with the
     * cumulated results and request info. Does not return anything since the processing of the request is done via the
     * specified callback.
     *
     * @param callable $callback
     * @param string|array $payload
     * @throws HttpRequesterException
     */
    public function stream(callable $callback, string|array $payload = "")
    {
        $this->setWriteCallback(function ($curl, $data) use ($callback) {
            $bytes = strlen($data);
            static $buf = '';
            $buf .= $data;
            $info = curl_getinfo($curl);
            while (1) {
                $pos = strpos($buf, "\n");
                if ($pos === false) {
                    break;
                }

                // @codeCoverageIgnoreStart
                $data = substr($buf, 0, $pos + 1);
                $buf = substr($buf, $pos + 1);
                if (strlen($data) > 50) {
                    $results = str_replace("data:", "", $data);
                    ($callback)($results, $info);
                }
                // @codeCoverageIgnoreEnd
            }
            return $bytes;
        });
        $this->execute($payload);
    }
}
