<?php namespace Zephyrus\Security;

class ContentSecurityPolicy
{
    const SELF = "'self'";
    const UNSAFE_INLINE = "'unsafe-inline'";
    const UNSAFE_EVAL = "'unsafe-eval'";
    const BASE64 = "data:";
    const NONE = "'none'";
    const ANY = "*";
    const HTTPS_ONLY = "https:";

    /**
     * Define script execution by requiring the presence of the specified nonce
     * on script elements. Must be used in script tag: <script nonce=...>
     *
     * @var string
     */
    private static $nonce;

    /**
     * Defines the defaults for most directives left unspecified. Generally, this
     * applies to any directive that ends with -src. The following directives don’t
     * use default-src as a fallback : base-uri, form-action, frame-ancestors,
     * plugin-types, report-uri, sandbox.
     *
     * @var string[]
     */
    private $defaultSources = [];

    /**
     * Define which scripts the protected resource can execute.
     *
     * @var string[]
     */
    private $scriptSources = [];

    /**
     * Is script-src’s counterpart for stylesheets.
     *
     * @var string[]
     */
    private $styleSources = [];

    /**
     * Allows control over Flash and other plugins.
     *
     * @var string[]
     */
    private $objectSources = [];

    /**
     * Defines the origins from which images can be loaded.
     *
     * @var string[]
     */
    private $imageSources = [];

    /**
     * Restricts the origins allowed to deliver video and audio.
     *
     * @var string[]
     */
    private $mediaSources = [];

    /**
     * Lists the URLs for workers and embedded frame contents. For example:
     * child-src https://youtube.com would enable embedding videos from YouTube
     * but not from other origins. Use this in place of the deprecated frame-src
     * directive.
     *
     * @var string[]
     */
    private $childSources = [];

    /**
     * Specifies the sources that can embed the current page. This directive
     * applies to <frame>, <iframe>, <embed>, and <applet> tags. This
     * directive cant be used in <meta> tags and applies only to non-HTML
     * resources.
     *
     * @var string[]
     */
    private $frameAncestors = [];

    /**
     * Specifies the origins that can serve web fonts. Google’s Web Fonts could
     * be enabled via font-src https://themes.googleusercontent.com.
     *
     * @var string[]
     */
    private $fontSources = [];

    /**
     * Limits the origins to which you can connect (via XHR, WebSockets, and
     * EventSource).
     *
     * @var string[]
     */
    private $connectSources = [];

    /**
     * Lists valid endpoints for submission from <form> tags.
     *
     * @var string[]
     */
    private $formActionSources = [];

    /**
     * Limits the kinds of plugins a page may invoke.
     *
     * @var string[]
     */
    private $pluginTypes = [];

    /**
     * Restricts the URLs that can appear in a page’s <base> element.
     *
     * @var string[]
     */
    private $baseUri = [];

    /**
     * Places restrictions on actions the page can take, rather than on resources
     * that the page can load. If the sandbox directive is present, the page will
     * be treated as though it was loaded inside of an iframe with a sandbox
     * attribute.
     *
     * @var string[]
     */
    private $sandbox = [];

    /**
     * The policy specified in report-only mode won’t block restricted
     * resources, but it will send violation reports to the location you
     * specify. You can even send both headers, enforcing one policy while
     * monitoring another. To do so, instantiate two object and set one as
     * report only.
     *
     * @var bool
     */
    private $reportOnly = false;

    /**
     * Specify to send the X-Content-Security-Policy along with the standard
     * one. Currently only for IE 11.
     *
     * @var bool
     */
    private $sendCompatibilityHeaders = false;

    /**
     * Instructs a user agent to activate or deactivate any heuristics used to
     * filter or block reflected cross-site scripting attacks, equivalent to
     * the effects of the non-standard X-XSS-Protection header.
     *
     * @var string
     */
    private $reflectedXss = "block";

    /**
     * Specifies a URL where a browser will send reports when a content security
     * policy is violated. This directive cant be used in <meta> tags.
     *
     * @var string
     */
    private $reportUri;

    /**
     * @return string
     */
    public static function getRequestNonce()
    {
        return self::$nonce;
    }

    /**
     * Generate a cryptographic nonce to be used for inline style and script.
     */
    public static function generateNonce()
    {
        self::$nonce = Cryptography::randomString(27);
    }

    /**
     * Build and send the complete CSP security header according to the object
     * specified data.
     */
    public function send()
    {
        $header = $this->buildCompleteHeader();
        $reportOnly = ($this->reportOnly) ? "-Report-Only" : "";
        header("Content-Security-Policy$reportOnly: " . $header);
        if ($this->sendCompatibilityHeaders) {
            header("X-Content-Security-Policy$reportOnly: " . $header);
        }
    }

    /**
     * @return string[]
     */
    public function getDefaultSources()
    {
        return $this->defaultSources;
    }

    /**
     * @param string[] $defaultSources
     */
    public function setDefaultSources($defaultSources)
    {
        $this->defaultSources = $defaultSources;
    }

    /**
     * @return string[]
     */
    public function getScriptSources()
    {
        return $this->scriptSources;
    }

    /**
     * @param string[] $scriptSources
     */
    public function setScriptSources($scriptSources)
    {
        $this->scriptSources = $scriptSources;
    }

    /**
     * @return string[]
     */
    public function getStyleSources()
    {
        return $this->styleSources;
    }

    /**
     * @param string[] $styleSources
     */
    public function setStyleSources($styleSources)
    {
        $this->styleSources = $styleSources;
    }

    /**
     * @return string[]
     */
    public function getObjectSources()
    {
        return $this->objectSources;
    }

    /**
     * @param string[] $objectSources
     */
    public function setObjectSources($objectSources)
    {
        $this->objectSources = $objectSources;
    }

    /**
     * @return string[]
     */
    public function getImageSources()
    {
        return $this->imageSources;
    }

    /**
     * @param string[] $imageSources
     */
    public function setImageSources($imageSources)
    {
        $this->imageSources = $imageSources;
    }

    /**
     * @return string[]
     */
    public function getMediaSources()
    {
        return $this->mediaSources;
    }

    /**
     * @param string[] $mediaSources
     */
    public function setMediaSources($mediaSources)
    {
        $this->mediaSources = $mediaSources;
    }

    /**
     * @return string[]
     */
    public function getChildSources()
    {
        return $this->childSources;
    }

    /**
     * @param string[] $childSources
     */
    public function setChildSources($childSources)
    {
        $this->childSources = $childSources;
    }

    /**
     * @return string[]
     */
    public function getFrameAncestors()
    {
        return $this->frameAncestors;
    }

    /**
     * @param string[] $frameAncestors
     */
    public function setFrameAncestors($frameAncestors)
    {
        $this->frameAncestors = $frameAncestors;
    }

    /**
     * @return string[]
     */
    public function getFontSources()
    {
        return $this->fontSources;
    }

    /**
     * @param string[] $fontSources
     */
    public function setFontSources($fontSources)
    {
        $this->fontSources = $fontSources;
    }

    /**
     * @return string[]
     */
    public function getConnectSources()
    {
        return $this->connectSources;
    }

    /**
     * @param string[] $connectSources
     */
    public function setConnectSources($connectSources)
    {
        $this->connectSources = $connectSources;
    }

    /**
     * @return string[]
     */
    public function getFormActionSources()
    {
        return $this->formActionSources;
    }

    /**
     * @param string[] $formActionSources
     */
    public function setFormActionSources($formActionSources)
    {
        $this->formActionSources = $formActionSources;
    }

    /**
     * @return string[]
     */
    public function getPluginTypes()
    {
        return $this->pluginTypes;
    }

    /**
     * @param string[] $pluginTypes
     */
    public function setPluginTypes($pluginTypes)
    {
        $this->pluginTypes = $pluginTypes;
    }

    /**
     * @return string[]
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    /**
     * @param string[] $baseUri
     */
    public function setBaseUri($baseUri)
    {
        $this->baseUri = $baseUri;
    }

    /**
     * @return string[]
     */
    public function getSandbox()
    {
        return $this->sandbox;
    }

    /**
     * @param string[] $sandbox
     */
    public function setSandbox($sandbox)
    {
        $this->sandbox = $sandbox;
    }

    /**
     * @return boolean
     */
    public function isOnlyReporting()
    {
        return $this->reportOnly;
    }

    /**
     * @param boolean $reportOnly
     */
    public function setReportOnly($reportOnly)
    {
        $this->reportOnly = $reportOnly;
    }

    /**
     * @return string
     */
    public function getReflectedXss()
    {
        return $this->reflectedXss;
    }

    /**
     * @param string $reflectedXss
     */
    public function setReflectedXss($reflectedXss)
    {
        $this->reflectedXss = $reflectedXss;
    }

    /**
     * @return string
     */
    public function getReportUri()
    {
        return $this->reportUri;
    }

    /**
     * @param string $reportUri
     */
    public function setReportUri($reportUri)
    {
        $this->reportUri = $reportUri;
    }

    /**
     * @return boolean
     */
    public function isCompatibilityHeadersSent()
    {
        return $this->sendCompatibilityHeaders;
    }

    /**
     * @param boolean $sendCompatibilityHeaders
     */
    public function setSendCompatibilityHeaders($sendCompatibilityHeaders)
    {
        $this->sendCompatibilityHeaders = $sendCompatibilityHeaders;
    }

    /**
     * Generates the complete CSP header base on object data.
     *
     * @return string
     */
    private function buildCompleteHeader()
    {
        $header = $this->buildHeaderLine('default-src', $this->defaultSources);
        $header .= $this->buildHeaderLine('script-src', $this->scriptSources);
        $header .= $this->buildHeaderLine('style-src', $this->styleSources);
        $header .= $this->buildHeaderLine('font-src', $this->fontSources);
        $header .= $this->buildHeaderLine('img-src', $this->imageSources);
        $header .= $this->buildHeaderLine('child-src', $this->childSources);
        $header .= $this->buildHeaderLine('base-uri', $this->baseUri);
        $header .= $this->buildHeaderLine('connect-src', $this->connectSources);
        $header .= $this->buildHeaderLine('form-action', $this->formActionSources);
        $header .= $this->buildHeaderLine('frame-ancestors', $this->frameAncestors);
        $header .= $this->buildHeaderLine('media-src', $this->mediaSources);
        $header .= $this->buildHeaderLine('object-src', $this->objectSources);
        $header .= $this->buildHeaderLine('plugin-types', $this->pluginTypes);
        $header .= $this->buildHeaderLine('sandbox', $this->sandbox);
        if (!empty($this->reportUri)) {
            $header .= 'report-uri ' . $this->reportUri . ';';
        }
        if (!empty($this->reflectedXss)) {
            $header .= 'reflected-xss ' . $this->reflectedXss . ';';
        }
        return $header;
    }

    /**
     * Retrieve a specific CSP line based on the provided sources.
     *
     * @param string $name
     * @param string[] $sources
     * @return string
     */
    private function buildHeaderLine($name, $sources)
    {
        $header = '';
        if (is_array($sources) && !empty($sources)) {
            $value = "";
            foreach ($sources as $source) {
                if (!empty($value)) {
                    $value .= ' ';
                }
                $value .= $source;
            }
            if ($name == "script-src" && !empty(self::$nonce)) {
                $header = "$name $value 'nonce-" . self::$nonce . "';";
            } else {
                $header = "$name $value;";
            }
        }
        return $header;
    }
}