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

    public function buildPollingSse($data, $eventName = 'stream', $retry = 1000): Response
    {
        $response = new Response(ContentType::SSE);
        $response->addHeader('Cache-Control', 'no-cache');
        ob_start();
        echo "event: $eventName" . PHP_EOL;
        echo "retry: " . $retry . PHP_EOL;
        echo "data: " . json_encode($data, JSON_FORCE_OBJECT | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) . PHP_EOL;
        echo PHP_EOL;
        $response->setContent(ob_get_clean());
        return $response;
    }

    public function buildStreamingSse($callback, $eventName, $sleep = 1): Response
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
        ob_end_clean();
        gc_enable();

        $callbackExecutionCounter = 1;
        $call = new Callback($callback);

        while (true) {
            if (connection_status() != CONNECTION_NORMAL || connection_aborted()) {
                break;
            }

            $data = $call->execute();
            if (!empty($data)) {
                $this->buildPollingSse($data, $eventName, $sleep * 1000)->sendContent();
            }

            if (@ob_get_level() > 0) {
                for ($i = 0; $i < @ob_get_level(); $i++) {
                    @ob_flush();
                }
            }
            @flush();

            sleep($sleep);
            $callbackExecutionCounter++;

            // Reduce potential memory leaks
            // @see https://stackoverflow.com/questions/29480791/while-loops-for-server-sent-events-are-causing-page-to-freeze
            if ($callbackExecutionCounter % 1000 == 0) {
                gc_collect_cycles();
                $callbackExecutionCounter = 1;
            }
        }

        if (@ob_get_level() > 0) {
            for ($i = 0; $i < @ob_get_level(); $i++) {
                @ob_flush();
            }
            @ob_end_clean();
        }

        return new Response(ContentType::SSE);
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
}
