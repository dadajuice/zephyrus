<?php namespace Zephyrus\Application\Views;

use Phug\PhugException;
use RuntimeException;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Form;
use Zephyrus\Network\Response;

class PugView extends View
{
    private PugEngine $engine;

    /**
     * @throws PhugException
     */
    public function __construct(string $pageToRender, array $configurations = [])
    {
        parent::__construct($pageToRender);
        $this->engine = new PugEngine($configurations);
    }

    public function render(array $args = []): Response
    {
        if ($this->isAvailable()) {
            $output = $this->engine->renderFromFile($this->getPath(), $args);
            Form::removeMemorizedValue();
            Flash::clearAll();
            Feedback::clear();
            return $this->buildResponse($output);
        }

        throw new RuntimeException("The specified view file [{$this->getPage()}] is not available (not readable or does not exists)");
    }

    protected function buildPathFromPage(string $pageToRender): string
    {
        return realpath(ROOT_DIR . '/app/Views/' . $pageToRender . '.pug');
    }
}
