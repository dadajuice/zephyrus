<?php namespace Zephyrus\Security;

use Zephyrus\Application\Configuration;
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
        $this->csrfGuard = new CsrfGuard();
        $this->secureHeader = new SecureHeader();
        $this->authorization = new Authorization();
    }

    public function before()
    {
        $failedRequirements = [];
        if (!$this->authorization->isAuthorized($this->request->getRequestedUri(), $failedRequirements)) {
            throw new UnauthorizedAccessException($this->request->getRequestedUri(), $failedRequirements);
        }
        $this->secureHeader->send();
        if (Configuration::getSecurityConfiguration('ids_enabled')) {
            IntrusionDetection::getInstance()->run();
        }
        if (Configuration::getSecurityConfiguration('csrf_guard_enabled')) {
            $this->csrfGuard->guard();
        }
    }

    public function after(?Response $response)
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
