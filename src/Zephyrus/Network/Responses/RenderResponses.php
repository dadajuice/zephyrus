<?php namespace Zephyrus\Network\Responses;

use Zephyrus\Application\Feedback;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Form;
use Zephyrus\Application\ViewBuilder;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

trait RenderResponses
{
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

    private function tryToBuildPhpView($page, $args = []): ?Response
    {
        $response = new Response(ContentType::HTML);
        $path = realpath(ROOT_DIR . '/app/Views/' . $page . '.php');
        if (file_exists($path) && is_readable($path)) {
            ob_start();
            foreach ($args as $name => $value) {
                $$name = $value;
            }
            $flash = Flash::readAll()["flash"];
            $feedback = Feedback::readAll()["feedback"];
            include $path;
            $response->setContent(ob_get_clean());
            Form::removeMemorizedValue();
            return $response;
        }
        return null;
    }
}
