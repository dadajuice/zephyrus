<?php namespace Zephyrus\Application\Views;

use RuntimeException;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Form;
use Zephyrus\Network\Response;

class PhpView extends View
{
    /**
     * Creates an HTML response with the given view arguments.
     *
     * @param array $args
     * @throws RuntimeException
     * @return Response
     */
    public function render(array $args = []): Response
    {
        if ($this->isAvailable()) {
            ob_start();
            foreach ($args as $name => $value) {
                $$name = $value;
            }
            $flash = Flash::readAll();
            $feedback = Feedback::readAll();
            include $this->getPath();
            $response = $this->buildResponse(ob_get_clean());
            Form::removeMemorizedValue();
            Flash::clearAll();
            Feedback::clearAll();
            return $response;
        }

        throw new RuntimeException("The specified view file [{$this->getPath()}] is not available (not readable or does not exists)");
    }

    protected function buildPathFromPage(string $pageToRender): string
    {
        return realpath(ROOT_DIR . '/app/Views/' . $pageToRender . '.php');
    }
}
