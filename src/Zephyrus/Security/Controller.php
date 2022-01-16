<?php namespace Zephyrus\Security;

use Zephyrus\Exceptions\IntrusionDetectionException;
use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;

abstract class Controller extends \Zephyrus\Application\Controller
{
    private CsrfGuard $csrfGuard;
    private SecureHeader $secureHeader;
    private Authorization $authorization;
    private IntrusionDetection $ids;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        $this->csrfGuard = new CsrfGuard($this->request);
        $this->authorization = new Authorization($this->request);
        $this->secureHeader = new SecureHeader();
        $this->ids = new IntrusionDetection($this->request);
    }

    /**
     * @throws UnauthorizedAccessException
     * @throws InvalidCsrfException
     * @throws IntrusionDetectionException
     */
    public function before(): ?Response
    {
        $failedRequirements = [];
        if (!$this->authorization->isAuthorized($this->request->getUri()->getPath(), $failedRequirements)) {
            throw new UnauthorizedAccessException($this->request->getUri()->getPath(), $failedRequirements);
        }
        $this->secureHeader->send();
        if ($this->ids->isEnabled()) {
            $this->ids->run();
        }
        if ($this->csrfGuard->isEnabled()) {
            $this->csrfGuard->run();
        }
        return null;
    }

    public function after(?Response $response): ?Response
    {
        if (!is_null($response)
            && $response->getContentType() == ContentType::HTML
            && $this->csrfGuard->isEnabled()
            && $this->csrfGuard->isHtmlIntegrationEnabled()) {
            $content = $this->csrfGuard->injectForms($response->getContent());
            $response->setContent($content);
        }
        return $response;
    }

    /**
     * @return CsrfGuard
     */
    public function getCsrfGuard(): CsrfGuard
    {
        return $this->csrfGuard;
    }

    /**
     * @return IntrusionDetection
     */
    public function getIntrusionDetection(): IntrusionDetection
    {
        return $this->ids;
    }

    /**
     * @return Authorization
     */
    public function getAuthorization(): Authorization
    {
        return $this->authorization;
    }

    /**
     * @return SecureHeader
     */
    public function getSecureHeader(): SecureHeader
    {
        return $this->secureHeader;
    }
}
