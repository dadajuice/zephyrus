<?php namespace Zephyrus\Security;

use RuntimeException;
use Zephyrus\Application\Configuration;
use Zephyrus\Application\Session;
use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Network\Request;

class CsrfGuard
{
    public const HEADER_NAME = 'HTTP_X_CSRF_NAME';
    public const HEADER_TOKEN = 'HTTP_X_CSRF_TOKEN';
    public const REQUEST_TOKEN_NAME = 'CSRFName';
    public const REQUEST_TOKEN_VALUE = 'CSRFToken';
    public const TOKEN_LENGTH = 48;
    public const DEFAULT_CONFIGURATIONS = [
        'enabled' => true, // Enable the CSRF mitigation feature
        'html_integration_enabled' => true, // Automatically insert needed HTML into forms
        'guard_methods' => ['POST', 'PUT', 'DELETE', 'PATCH'], // List of guarded methods
        'exceptions' => [] // List of route exceptions (e.g. ['\/test.*'] meaning all routes beginning with /test)
    ];

    /**
     * Keeps a linked reference to the Request instance given in the constructor. Meaning that the request could evolve
     * outside the CsrfGuard instance and still be up-to-date.
     *
     * @var Request|null
     */
    private ?Request $request;

    /**
     * Determines the HTTP request methods that should be secured by the CSRF mitigation. It implies that for EVERY
     * request of these types, the CSRF token should be provided. All forms should follow a strict REST philosophy
     * meaning that all form processing should pass through POST, PUT, PATCH or DELETE only.
     *
     * @var array
     */
    private array $guardedMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

    /**
     * List of routes to ignore the CSRF mitigation no matter the HTTP method. Normally, all routes for the guarded
     * HTTP methods should pass through the CSRF mitigation but, it may happen that some routes are exempt of
     * mitigation. For such cases, the exceptions should be used. Accepts regex for the route definition.
     *
     * @var array
     */
    private array $exceptions = [];

    /**
     * Determines if the CSRF mitigation is active. Should be verified before calling the run method.
     *
     * @var bool
     */
    private bool $enabled = true;

    /**
     * Determines if the CSRF mitigation should inject the needed HTML fields automatically. Since every form will need
     * proper inclusion of specific tokens, it is best to use the automatic integration.
     *
     * @var bool
     */
    private bool $htmlIntegrationEnabled = true;

    /**
     * Loaded configurations for the CSRF mitigation.
     *
     * @var array
     */
    private array $configurations;

    public function __construct(?Request &$request, array $configurations = [])
    {
        $this->request = &$request;
        $this->initializeConfigurations($configurations);
        $this->initializeEnabledState();
        $this->initializeAutomaticHtmlIntegration();
        $this->initializeGuardedMethods();
        $this->initializeExceptions();
    }

    /**
     * Verifies if the CSRF mitigation is enabled based on the instance configuration. Should be use as a condition to
     * execute the run method.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Verifies if the CSRF mitigation is configured to automatically inject HTML into forms.
     *
     * @return bool
     */
    public function isHtmlIntegrationEnabled(): bool
    {
        return $this->htmlIntegrationEnabled;
    }

    /**
     * Generates and returns the corresponding HTML hidden fields for the CSRF mitigation. Should be used for a custom
     * approach to form data injection. Not needed if the html_integration_enabled configuration. In that case, the
     * injectForms() method should be used instead.
     *
     * @return string
     */
    public function generateHiddenFields(): string
    {
        $name = $this->generateFormName();
        $token = $this->generateToken($name);
        $html = '<input type="hidden" name="' . self::REQUEST_TOKEN_NAME . '" value="' . $name . '" />';
        $html .= '<input type="hidden" name="' . self::REQUEST_TOKEN_VALUE . '" value="' . $token . '" />';
        return $html;
    }

    /**
     * Proceeds to filter the current request for any CSRF mismatch. Forms must provide its unique name and
     * corresponding generated csrf token. Will throw a InvalidCsrfException on failure.
     *
     * @throws InvalidCsrfException
     */
    public function run()
    {
        if (!$this->isExempt()) {
            $formName = $this->getProvidedFormName();
            $providedToken = $this->getProvidedCsrfToken();
            if (is_null($formName) || is_null($providedToken)) {
                throw new InvalidCsrfException();
            }
            if (!$this->validateToken($formName, $providedToken)) {
                throw new InvalidCsrfException();
            }
        }
    }

    /**
     * Automatically adds CSRF hidden fields to any forms present in the given HTML. This method is to be used with
     * automatic injection behavior. If a form contains a "nocsrf" HTML property, the CSRF mitigation is skipped for
     * this specific form.
     *
     * @param string $html
     * @return string
     */
    public function injectForms(string $html): string
    {
        preg_match_all("/<form(.*?)>(.*?)<\\/form>/is", $html, $matches, PREG_SET_ORDER);
        if (is_array($matches)) {
            foreach ($matches as $match) {
                if (str_contains($match[1], "nocsrf")) {
                    continue;
                }
                $hiddenFields = self::generateHiddenFields();
                $html = str_replace($match[0], "<form$match[1]>$hiddenFields$match[2]</form>", $html);
            }
        }
        return $html;
    }

    /**
     * @return bool
     */
    public function isGetSecured(): bool
    {
        return in_array('GET', $this->guardedMethods);
    }

    /**
     * @return bool
     */
    public function isPostSecured(): bool
    {
        return in_array('POST', $this->guardedMethods);
    }

    /**
     * @return bool
     */
    public function isPutSecured(): bool
    {
        return in_array('PUT', $this->guardedMethods);
    }

    /**
     * @return bool
     */
    public function isPatchSecured(): bool
    {
        return in_array('PATCH', $this->guardedMethods);
    }

    /**
     * @return bool
     */
    public function isDeleteSecured(): bool
    {
        return in_array('DELETE', $this->guardedMethods);
    }

    /**
     * Validates if the current request is exempt of CSRF verification. Can happen if the HTTP request method is not
     * filtered or the route matches one of the defined exceptions.
     *
     * @return bool
     */
    private function isExempt(): bool
    {
        if (!$this->isHttpMethodFiltered(strtoupper($this->request->getMethod()))) {
            return true;
        }
        foreach ($this->exceptions as $exceptionRegex) {
            if (preg_match('/^' . $exceptionRegex . '$/', $this->request->getRoute())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generates and stores in the current session a cryptographically random token that shall be validated during the
     * run method.
     *
     * @param string $formName
     * @return string
     */
    private function generateToken(string $formName): string
    {
        $token = Cryptography::randomString(self::TOKEN_LENGTH);
        $csrfData = Session::getInstance()->read('__CSRF_TOKEN', []);
        $csrfData[$formName] = $token;
        Session::getInstance()->set('__CSRF_TOKEN', $csrfData);
        return $token;
    }

    /**
     * Returns a random name to be used for a form csrf token.
     *
     * @return string
     */
    private function generateFormName(): string
    {
        return "CSRFGuard_" . mt_rand(0, mt_getrandmax());
    }

    /**
     * Validates the given token with the one stored for the specified form name. Once validated, good or not, the token
     * is removed from the session.
     *
     * @param string $formName
     * @param string $token
     * @return bool
     */
    private function validateToken(string $formName, string $token): bool
    {
        $sortedCsrf = $this->getStoredCsrfToken($formName);
        if (!is_null($sortedCsrf)) {
            $csrfData = Session::getInstance()->read('__CSRF_TOKEN', []);
            if (is_null($this->request->getHeader('CSRF_KEEP_ALIVE'))
                && is_null($this->request->getParameter('CSRF_KEEP_ALIVE'))) {
                $csrfData[$formName] = '';
                Session::getInstance()->set('__CSRF_TOKEN', $csrfData);
            }
            return hash_equals($sortedCsrf, $token);
        }
        return false;
    }

    /**
     * Obtains the CSRF token stored by the server for the corresponding client. Returns null if undefined.
     *
     * @param string $formName
     * @return null|string
     */
    private function getStoredCsrfToken(string $formName): ?string
    {
        $csrfData = Session::getInstance()->read('__CSRF_TOKEN');
        if (is_null($csrfData)) {
            return null;
        }
        return $csrfData[$formName] ?? null;
    }

    /**
     * Obtains the CSRF token provided by the client either by request data or by an HTTP header (e.g. Ajax based
     * requests). Returns null if undefined.
     *
     * @return null|string
     */
    private function getProvidedCsrfToken(): ?string
    {
        $token = $this->request->getParameter(self::REQUEST_TOKEN_VALUE);
        if (is_null($token)) {
            $token = $this->request->getHeader(self::HEADER_TOKEN);
        }
        return $token;
    }

    /**
     * Obtains the form name provided by the client either by request data or by an HTTP header (e.g. Ajax based
     * requests). Returns null if undefined.
     *
     * @return null|string
     */
    private function getProvidedFormName(): ?string
    {
        $formName = $this->request->getParameter(self::REQUEST_TOKEN_NAME);
        if (is_null($formName)) {
            $formName = $this->request->getHeader(self::HEADER_NAME);
        }
        return $formName;
    }

    /**
     * Checks if the specified method should be filtered.
     *
     * @param string $method
     * @return bool
     */
    private function isHttpMethodFiltered(string $method): bool
    {
        $method = strtoupper($method);
        if ($this->isGetSecured() && $method == "GET") {
            return true;
        } elseif ($this->isPostSecured() && $method == "POST") {
            return true;
        } elseif ($this->isPutSecured() && $method == "PUT") {
            return true;
        } elseif ($this->isPatchSecured() && $method == "PATCH") {
            return true;
        } elseif ($this->isDeleteSecured() && $method == "DELETE") {
            return true;
        }
        return false;
    }

    private function initializeConfigurations(array $configurations)
    {
        if (empty($configurations)) {
            $configurations = Configuration::getConfiguration('csrf') ?? self::DEFAULT_CONFIGURATIONS;
        }
        $this->configurations = $configurations;
    }

    private function initializeEnabledState()
    {
        if (isset($this->configurations['enabled'])) {
            $this->enabled = $this->configurations['enabled'];
        }
    }

    private function initializeAutomaticHtmlIntegration()
    {
        if (isset($this->configurations['html_integration_enabled'])) {
            $this->htmlIntegrationEnabled = $this->configurations['html_integration_enabled'];
        }
    }

    private function initializeGuardedMethods()
    {
        if (isset($this->configurations['guard_methods'])) {
            foreach ($this->configurations['guard_methods'] as $method) {
                if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
                    throw new RuntimeException("CSRF guard methods is invalid. Must be an array containing a combinaison of the following values 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'. ");
                }
            }
            $this->guardedMethods = $this->configurations['guard_methods'];
        }
    }

    private function initializeExceptions()
    {
        if (isset($this->configurations['exceptions']) && !empty($this->configurations['exceptions'])) {
            $this->exceptions = $this->configurations['exceptions'];
        }
    }
}
