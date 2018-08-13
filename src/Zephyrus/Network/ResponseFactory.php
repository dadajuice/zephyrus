<?php namespace Zephyrus\Network;

use Zephyrus\Application\Callback;
use Zephyrus\Application\Form;
use Zephyrus\Application\ViewBuilder;

class ResponseFactory
{
    /**
     * @var ResponseFactory
     */
    private static $instance = null;

    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function buildView($page, $args = []): Response
    {
        $response = $this->tryToBuildPhpView($page, $args);
        if (!is_null($response)) {
            return $response;
        }
        $response = new Response(ContentType::HTML);
        $view = ViewBuilder::getInstance()->build($page);
        $response->setContent($view->render($args));
        return $response;
    }

    public function buildHtml(string $data): Response
    {
        $response = new Response(ContentType::HTML);
        $response->setContent($data);
        return $response;
    }

    public function buildJson($data): Response
    {
        $response = new Response(ContentType::JSON);
        $response->setContent(json_encode($data));
        return $response;
    }

    public function buildDownload(string $filePath, ?string $filename = null): Response
    {
        if (is_null($filename)) {
            $filename = basename($filePath);
        }
        $contentLength = filesize($filePath);
        $response = new Response(ContentType::APPLICATION);
        $response->addHeader("Pragma", "public");
        $response->addHeader("Expires", "0");
        $response->addHeader("Cache-Control", "must-revalidate, post-check=0, pre-check=0");
        $response->addHeader("Cache-Control", "public");
        $response->addHeader("Content-Description", "File Transfer");
        $response->addHeader("Content-Disposition", 'attachment; filename="' . $filename . '"');
        $response->addHeader("Content-Transfer-Encoding", "binary");
        $response->addHeader("Content-Length", $contentLength);
        ob_start();
        @readfile($filePath);
        $content = ob_get_clean();
        $response->setContent($content);
        return $response;
    }

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
                if (connection_aborted()) {
                    exit;
                }
                $that->outputSseMessage($id, $data);
                @flush();
            });
        });
        return $response;
    }

    public function buildRedirect(string $url): Response
    {
        $response = new Response(ContentType::PLAIN, 303);
        $response->addHeader('Location', $url);
        return $response;
    }

    public function buildXml($data, $root = ""): Response
    {
        $response = new Response(ContentType::XML);
        if ((!$data instanceof \SimpleXMLElement) && !is_array($data)) {
            throw new \RuntimeException("Cannot parse specified data as XML");
        }
        if ($data instanceof \SimpleXMLElement) {
            $response->setContent($data->asXML());
        }
        if (is_array($data)) {
            $xml = new \SimpleXMLElement('<' . $root . '/>');
            $this->arrayToXml($data, $xml);
            $response->setContent($xml->asXML());
        }
        return $response;
    }

    private function arrayToXml($data, \SimpleXMLElement &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'node' . $key;
            }
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
                return;
            }
            $xml->addChild("$key", htmlspecialchars("$value"));
        }
    }

    private function tryToBuildPhpView($page, $args = []): ?Response
    {
        $response = new Response(ContentType::HTML);
        $path = ROOT_DIR . '/app/Views/' . $page . '.php';
        if (file_exists($path) && is_readable($path)) {
            ob_start();
            foreach ($args as $name => $value) {
                $$name = $value;
            }
            include $path;
            $response->setContent(ob_get_clean());
            Form::removeMemorizedValue();
            return $response;
        }
        return null;
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
