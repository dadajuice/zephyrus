<?php namespace Zephyrus\Security;

class ContentSecurityPolicy
{
    const SELF = "'self'";
    const UNSAFE_INLINE = "'unsafe-inline'";
    const UNSAFE_EVAL = "'unsafe-eval'";
    const BASE64 = "data:";
    const BLOB = "blob:";
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
        'worker-src',
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
     * Define script execution by requiring the presence of the specified nonce on script elements. Must be used in
     * script tag: <script nonce=...>.
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
     * The policy specified in report-only mode won’t block restricted resources, but it will send violation reports to
     * the location you specify. You can even send both headers, enforcing one policy while monitoring another. To do
     * so, instantiate two object and set one as report only.
     *
     * @var bool
     */
    private $reportOnly = false;

    /**
     * Specify to send the X-Content-Security-Policy along with the standard one. Currently only for IE 11.
     *
     * @var bool
     */
    private $compatible = false;

    /**
     * Specifies a URL where a browser will send reports when a content security policy is violated. This directive
     * cant be used in <meta> tags.
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
     * Build and send the complete CSP security header according to the object specified data.
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
    public function getAllHeader(): array
    {
        return $this->headers;
    }

    /**
     * Defines the defaults for most directives left unspecified. Generally, this applies to any directive that ends
     * with -src. The following directives don’t use default-src as a fallback : base-uri, form-action, frame-ancestors,
     * plugin-types, report-uri, sandbox.
     *
     * @param string[] $defaultSources
     */
    public function setDefaultSources(array $defaultSources)
    {
        $this->headers['default-src'] = $defaultSources;
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
     * Is script-src’s counterpart for stylesheets.
     *
     * @param string[] $styleSources
     */
    public function setStyleSources(array $styleSources)
    {
        $this->headers['style-src'] = $styleSources;
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
     * Defines the origins from which images can be loaded.
     *
     * @param string[] $imageSources
     */
    public function setImageSources(array $imageSources)
    {
        $this->headers['img-src'] = $imageSources;
    }

    /**
     * Defines the origins from which workers can be started.
     *
     * @param string[] $workerSources
     */
    public function setWorkerSources(array $workerSources)
    {
        $this->headers['worker-src'] = $workerSources;
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
     * Lists the URLs for workers and embedded frame contents. For example: child-src https://youtube.com would enable
     * embedding videos from YouTube but not from other origins. Use this in place of the deprecated frame-src
     * directive.
     *
     * @param string[] $childSources
     */
    public function setChildSources(array $childSources)
    {
        $this->headers['child-src'] = $childSources;
    }

    /**
     * Specifies the sources that can embed the current page. This directive applies to <frame>, <iframe>, <embed>,
     * and <applet> tags. This directive cant be used in <meta> tags and applies only to non-HTML resources.
     *
     * @param string[] $frameAncestors
     */
    public function setFrameAncestors(array $frameAncestors)
    {
        $this->headers['frame-ancestors'] = $frameAncestors;
    }

    /**
     * Specifies the origins that can serve web fonts. Google’s Web Fonts could be enabled via the directive
     * font-src https://themes.googleusercontent.com.
     *
     * @param string[] $fontSources
     */
    public function setFontSources(array $fontSources)
    {
        $this->headers['font-src'] = $fontSources;
    }

    /**
     * Limits the origins to which you can connect (via XHR, WebSockets, and EventSource).
     *
     * @param string[] $connectSources
     */
    public function setConnectSources(array $connectSources)
    {
        $this->headers['connect-src'] = $connectSources;
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
     * Limits the kinds of plugins a page may invoke.
     *
     * @param string[] $pluginTypes
     */
    public function setPluginTypes(array $pluginTypes)
    {
        $this->headers['plugin-types'] = $pluginTypes;
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
     * Manually set any source type. Helpful for any source type that may be missing from this class or any iterative
     * processing of a subset of source types.
     *
     * @param string $sourceType
     * @param string[] $sources
     */
    public function setSourceType(string $sourceType, array $sources)
    {
        $this->headers[$sourceType] = $sources;
    }

    /**
     * Places restrictions on actions the page can take, rather than on resources that the page can load. If the sandbox
     * directive is present, the page will be treated as though it was loaded inside of an iframe with a sandbox
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
     * @param string $reportUri
     */
    public function setReportUri(string $reportUri)
    {
        $this->reportUri = $reportUri;
    }

    /**
     * @param bool $compatible
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
