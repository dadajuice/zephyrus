<?php namespace Zephyrus\Security;

use Zephyrus\Application\Configuration;
use Zephyrus\Exceptions\IntrusionDetectionException;
use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;

abstract class Controller extends \Zephyrus\Application\Controller
{
    /**
     * @var CsrfGuard
     */
    private $csrfGuard;

    /**
     * @var SecureHeader
     */
    private $secureHeader;

    /**
     * @var Authorization
     */
    private $authorization;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        $this->csrfGuard = new CsrfGuard($this->request);
        $this->authorization = new Authorization($this->request);
        $this->secureHeader = new SecureHeader();
    }

    /**
     * @throws UnauthorizedAccessException
     * @throws InvalidCsrfException
     */
    public function before(): ?Response
    {
        $failedRequirements = [];
        if (!$this->authorization->isAuthorized($this->request->getUri()->getPath(), $failedRequirements)) {
            throw new UnauthorizedAccessException($this->request->getUri()->getPath(), $failedRequirements);
        }
        $this->secureHeader->send();
        if (Configuration::getSecurityConfiguration('csrf_guard_enabled')) {
            $this->csrfGuard->guard();
        }
        return null;
    }

    public function after(?Response $response): ?Response
    {
        if (!is_null($response)
            && $response->getContentType() == ContentType::HTML
            && Configuration::getSecurityConfiguration('csrf_guard_enabled')
            && Configuration::getSecurityConfiguration('csrf_guard_automatic_html')) {
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
