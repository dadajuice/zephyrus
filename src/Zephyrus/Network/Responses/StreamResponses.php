<?php namespace Zephyrus\Network\Responses;

use Zephyrus\Application\Callback;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

trait StreamResponses
{
    public function buildPollingSse($data, $eventId = 'stream', $retry = 1000): Response
    {
        $response = new Response(ContentType::SSE);
        $response->addHeader('Cache-Control', 'no-cache');
        ob_start();
        $this->outputSseMessage($eventId, $data, $retry);
        $response->setContent(ob_get_clean());
        return $response;
    }

    public function buildStreamingSse($callback, $eventId, $sleep = 1): Response
    {
        $response = new Response(ContentType::SSE);
        $that = $this;
        $response->setContentCallback(function () use ($callback, $eventId, $sleep, $that) {
            $that->initializeStreaming();
            $callbackExecutionCounter = 1;
            $call = new Callback($callback);
            while (connection_status() == CONNECTION_NORMAL && !connection_aborted()) {
                $data = $call->execute();
                if ($data === false) {
                    break;
                }
                if (!empty($data)) {
                    $that->buildPollingSse($data, $eventId, $sleep * 1000)->sendContent();
                }
                @flush();
                sleep($sleep);
                $callbackExecutionCounter++;

                // Reduce potential memory leaks
                if ($callbackExecutionCounter % 1000 == 0) {
                    gc_collect_cycles();
                    $callbackExecutionCounter = 1;
                }
            }
        });
        return $response;
    }

    public function buildFlowSse($callback)
    {
        $response = new Response(ContentType::SSE);
        $that = $this;
        $response->setContentCallback(function () use ($callback, $that) {
            $that->initializeStreaming();
            $call = new Callback($callback);
            $call->execute(function ($id, $data) use ($that) {
                // @codeCoverageIgnoreStart
                if (connection_aborted()) {
                    exit;
                }
                // @codeCoverageIgnoreEnd
                $that->outputSseMessage($id, $data);
                @flush();
            });
        });
        return $response;
    }

    private function initializeStreaming()
    {
        // Make session readonly to prevent hang from other pages
        // session_start(); is optional since its always starting from kernel
        session_write_close();
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header("Connection: keep-alive");
        set_time_limit(0);
        ignore_user_abort(true);
        ini_set('auto_detect_line_endings', 1);
        ini_set('max_execution_time', '0');
        // @codeCoverageIgnoreStart
        // ob_get_level should always be at 1 when using SSE, this if is for
        // test coverage to allow PHPUnit to output buffer results.
        if (ob_get_level() < 2) {
            ob_end_clean();
        }
        // @codeCoverageIgnoreEnd
        gc_enable();
    }

    private function outputSseMessage($eventId, $data, $retry = null)
    {
        $encodeOptions = JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS;
        echo "id: $eventId" . PHP_EOL;
        if (!is_null($retry)) {
            echo "retry: " . $retry . PHP_EOL;
        }
        echo "data: " . json_encode($data, $encodeOptions) . PHP_EOL;
        echo PHP_EOL;
    }
}
