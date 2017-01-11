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
     * Defines the supported source types.
     *
     * @var array
     */
    private $sourceTypes = [
        'default-src',
        'script-src',
        'style-src',
        'font-src',
        'img-src',
        'child-src',
        'base-uri',
        'connect-src',
        'form-action',
        'frame-ancestors',
        'media-src',
        'object-src',
        'plugin-types',
        'sandbox'
    ];

    /**
     * Define script execution by requiring the presence of the specified nonce
     * on script elements. Must be used in script tag: <script nonce=...>
     *
     * @var string
     */
    private static $nonce = null;

    /**
     * Defines the content for each supported source types.
     *
     * @var array
     */
    private $headers = [];

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
    private $compatible = false;

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
     * Generates a nonce if its has not been already done.
     *
     * @return string
     */
    public static function getRequestNonce()
    {
        if (is_null(self::$nonce)) {
            self::generateNonce();
        }
        return self::$nonce;
    }

    public function __construct()
    {
        foreach ($this->sourceTypes as $type) {
            $this->headers[$type] = [];
        }
        if (empty(self::$nonce)) {
            self::generateNonce();
        }
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
        if ($this->compatible) {
            header("X-Content-Security-Policy$reportOnly: " . $header);
        }
    }

    /**
     * @return string[]
     */
    public function getDefaultSources(): array
    {
        return $this->headers['default-src'];
    }

    /**
     * Defines the defaults for most directives left unspecified. Generally, this
     * applies to any directive that ends with -src. The following directives don’t
     * use default-src as a fallback : base-uri, form-action, frame-ancestors,
     * plugin-types, report-uri, sandbox.
     *
     * @param string[] $defaultSources
     */
    public function setDefaultSources(array $defaultSources)
    {
        $this->headers['default-src'] = $defaultSources;
    }

    /**
     * @return string[]
     */
    public function getScriptSources(): array
    {
        return $this->headers['script-src'];
    }

    /**
     * Define which scripts the protected resource can execute.
     *
     * @param string[] $scriptSources
     */
    public function setScriptSources(array $scriptSources)
    {
        $this->headers['script-src'] = $scriptSources;
    }

    /**
     * @return string[]
     */
    public function getStyleSources(): array
    {
        return $this->headers['style-src'];
    }

    /**
     * Is script-src’s counterpart for stylesheets.
     *
     * @param string[] $styleSources
     */
    public function setStyleSources(array $styleSources)
    {
        $this->headers['style-src'] = $styleSources;
    }

    /**
     * @return string[]
     */
    public function getObjectSources(): array
    {
        return $this->headers['object-src'];
    }

    /**
     * Allows control over Flash and other plugins.
     *
     * @param string[] $objectSources
     */
    public function setObjectSources(array $objectSources)
    {
        $this->headers['object-src'] = $objectSources;
    }

    /**
     * @return string[]
     */
    public function getImageSources(): array
    {
        return $this->headers['img-src'];
    }

    /**
     * Defines the origins from which images can be loaded.
     *
     * @param string[] $imageSources
     */
    public function setImageSources(array $imageSources)
    {
        $this->headers['img-src'] = $imageSources;
    }

    /**
     * @return string[]
     */
    public function getMediaSources(): array
    {
        return $this->headers['media-src'];
    }

    /**
     * Restricts the origins allowed to deliver video and audio.
     *
     * @param string[] $mediaSources
     */
    public function setMediaSources(array $mediaSources)
    {
        $this->headers['media-src'] = $mediaSources;
    }

    /**
     * @return string[]
     */
    public function getChildSources(): array
    {
        return $this->headers['child-src'];
    }

    /**
     * Lists the URLs for workers and embedded frame contents. For example:
     * child-src https://youtube.com would enable embedding videos from YouTube
     * but not from other origins. Use this in place of the deprecated frame-src
     * directive.
     *
     * @param string[] $childSources
     */
    public function setChildSources(array $childSources)
    {
        $this->headers['child-src'] = $childSources;
    }

    /**
     * @return string[]
     */
    public function getFrameAncestors(): array
    {
        return $this->headers['frame-ancestors'];
    }

    /**
     * Specifies the sources that can embed the current page. This directive
     * applies to <frame>, <iframe>, <embed>, and <applet> tags. This
     * directive cant be used in <meta> tags and applies only to non-HTML
     * resources.
     *
     * @param string[] $frameAncestors
     */
    public function setFrameAncestors(array $frameAncestors)
    {
        $this->headers['frame-ancestors'] = $frameAncestors;
    }

    /**
     * @return string[]
     */
    public function getFontSources(): array
    {
        return $this->headers['font-src'];
    }

    /**
     * Specifies the origins that can serve web fonts. Google’s Web Fonts could
     * be enabled via font-src https://themes.googleusercontent.com.
     *
     * @param string[] $fontSources
     */
    public function setFontSources(array $fontSources)
    {
        $this->headers['font-src'] = $fontSources;
    }

    /**
     * @return string[]
     */
    public function getConnectSources(): array
    {
        return $this->headers['connect-src'];
    }

    /**
     * Limits the origins to which you can connect (via XHR, WebSockets, and
     * EventSource).
     *
     * @param string[] $connectSources
     */
    public function setConnectSources(array $connectSources)
    {
        $this->headers['connect-src'] = $connectSources;
    }

    /**
     * @return string[]
     */
    public function getFormActionSources(): array
    {
        return $this->headers['form-action'];
    }

    /**
     * Lists valid endpoints for submission from <form> tags.
     *
     * @param string[] $formActionSources
     */
    public function setFormActionSources(array $formActionSources)
    {
        $this->headers['form-action'] = $formActionSources;
    }

    /**
     * @return string[]
     */
    public function getPluginTypes(): array
    {
        return $this->headers['plugin-types'];
    }

    /**
     * Limits the kinds of plugins a page may invoke.
     *
     * @param string[] $pluginTypes
     */
    public function setPluginTypes(array $pluginTypes)
    {
        $this->headers['plugin-types'] = $pluginTypes;
    }

    /**
     * @return string[]
     */
    public function getBaseUri(): array
    {
        return $this->headers['base-uri'];
    }

    /**
     * Restricts the URLs that can appear in a page’s <base> element.
     *
     * @param string[] $baseUri
     */
    public function setBaseUri(array $baseUri)
    {
        $this->headers['base-uri'] = $baseUri;
    }

    /**
     * @return string[]
     */
    public function getSandbox(): array
    {
        return $this->headers['sandbox'];
    }

    /**
     * Places restrictions on actions the page can take, rather than on resources
     * that the page can load. If the sandbox directive is present, the page will
     * be treated as though it was loaded inside of an iframe with a sandbox
     * attribute.
     *
     * @param string[] $sandbox
     */
    public function setSandbox(array $sandbox)
    {
        $this->headers['sandbox'] = $sandbox;
    }

    /**
     * @return bool
     */
    public function isOnlyReporting(): bool
    {
        return $this->reportOnly;
    }

    /**
     * @param bool $reportOnly
     */
    public function setReportOnly(bool $reportOnly)
    {
        $this->reportOnly = $reportOnly;
    }

    /**
     * @return string
     */
    public function getReflectedXss(): string
    {
        return $this->reflectedXss;
    }

    /**
     * @param string $reflectedXss
     */
    public function setReflectedXss(string $reflectedXss)
    {
        $this->reflectedXss = $reflectedXss;
    }

    /**
     * @return string
     */
    public function getReportUri(): string
    {
        return $this->reportUri;
    }

    /**
     * @param string $reportUri
     */
    public function setReportUri(string $reportUri)
    {
        $this->reportUri = $reportUri;
    }

    /**
     * @return boolean
     */
    public function isCompatible(): bool
    {
        return $this->compatible;
    }

    /**
     * @param boolean $compatible
     */
    public function setCompatible(bool $compatible)
    {
        $this->compatible = $compatible;
    }

    /**
     * Generates the complete CSP header base on object data.
     *
     * @return string
     */
    private function buildCompleteHeader(): string
    {
        $header = "";
        foreach ($this->headers as $sourceType => $value) {
            $header .= $this->buildHeaderLine($sourceType, $value);
        }
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
    private function buildHeaderLine(string $name, array $sources): string
    {
        $header = '';
        if (!empty($sources)) {
            $value = "";
            foreach ($sources as $source) {
                if (!empty($value)) {
                    $value .= ' ';
                }
                $value .= $source;
            }
            $header = ($name == "script-src" && !empty(self::$nonce))
                ? "$name $value 'nonce-" . self::$nonce . "';"
                : "$name $value;";
        }
        return $header;
    }

    /**
     * Generate a cryptographic nonce to be used for inline style and script.
     */
    private static function generateNonce()
    {
        self::$nonce = Cryptography::randomString(27);
    }
}
