<?php namespace Zephyrus\Security;

class ContentSecurityPolicy
{
    public const SELF = "'self'";
    public const UNSAFE_INLINE = "'unsafe-inline'";
    public const UNSAFE_EVAL = "'unsafe-eval'";
    public const BASE64 = "data:";
    public const BLOB = "blob:";
    public const NONE = "'none'";
    public const ANY = "*";
    public const HTTPS_ONLY = "https:";

    /**
     * Define script execution by requiring the presence of the specified nonce on script elements. Must be used in
     * script tag: <script nonce=...>.
     */
    private static ?string $nonce = null;

    private array $supportedSourceTypes = [
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
    private array $headers = [];

    /**
     * The policy specified in report-only mode won’t block restricted resources, but it will send violation reports to
     * the location you specify. You can even send both headers, enforcing one policy while monitoring another. To do
     * so, instantiate two object and set one as report only.
     *
     * @var bool
     */
    private bool $reportOnly = false;

    /**
     * Specify to send the X-Content-Security-Policy along with the standard one. Currently only for IE 11.
     *
     * @var bool
     */
    private bool $compatible = false;

    /**
     * Specifies a URL where a browser will send reports when a content security policy is violated. This directive
     * cant be used in <meta> tags.
     *
     * @var string
     */
    private string $reportUri;

    /**
     * Returns the generated cryptographic nonce for inline styles and scripts. One per request.
     */
    public static function nonce(): string
    {
        if (is_null(self::$nonce)) {
            self::$nonce = Cryptography::randomString(27);
        }
        return self::$nonce;
    }

    public function __construct()
    {
        foreach ($this->supportedSourceTypes as $type) {
            $this->headers[$type] = [];
        }
    }

    /**
     * Build and send the complete CSP security header according to the object specified data.
     */
    public function send(): void
    {
        $header = $this->buildCompleteHeader();
        $reportOnly = ($this->reportOnly) ? "-Report-Only" : "";
        header("Content-Security-Policy$reportOnly: " . $header);
        if ($this->compatible) {
            header("X-Content-Security-Policy$reportOnly: " . $header);
        }
    }

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
    public function setDefaultSources(array $defaultSources): void
    {
        $this->headers['default-src'] = $defaultSources;
    }

    public function addDefaultSource(string $source): void
    {
        $this->addSource('default-src', $source);
    }

    /**
     * Define which scripts the protected resource can execute.
     *
     * @param string[] $scriptSources
     */
    public function setScriptSources(array $scriptSources): void
    {
        $this->headers['script-src'] = $scriptSources;
    }

    public function addScriptSource(string $source): void
    {
        $this->addSource('script-src', $source);
    }

    /**
     * Is script-src’s counterpart for stylesheets.
     *
     * @param string[] $styleSources
     */
    public function setStyleSources(array $styleSources): void
    {
        $this->headers['style-src'] = $styleSources;
    }

    public function addStyleSource(string $source): void
    {
        $this->addSource('style-src', $source);
    }

    /**
     * Allows control over Flash and other plugins.
     *
     * @param string[] $objectSources
     */
    public function setObjectSources(array $objectSources): void
    {
        $this->headers['object-src'] = $objectSources;
    }

    public function addObjectSource(string $source): void
    {
        $this->addSource('object-src', $source);
    }

    /**
     * Defines the origins from which images can be loaded.
     *
     * @param string[] $imageSources
     */
    public function setImageSources(array $imageSources): void
    {
        $this->headers['img-src'] = $imageSources;
    }

    public function addImageSource(string $source): void
    {
        $this->addSource('img-src', $source);
    }

    /**
     * Defines the origins from which workers can be started.
     *
     * @param string[] $workerSources
     */
    public function setWorkerSources(array $workerSources): void
    {
        $this->headers['worker-src'] = $workerSources;
    }

    public function addWorkerSource(string $source): void
    {
        $this->addSource('worker-src', $source);
    }

    /**
     * Restricts the origins allowed to deliver video and audio.
     *
     * @param string[] $mediaSources
     */
    public function setMediaSources(array $mediaSources): void
    {
        $this->headers['media-src'] = $mediaSources;
    }

    public function addMediaSource(string $source): void
    {
        $this->addSource('media-src', $source);
    }

    /**
     * Lists the URLs for workers and embedded frame contents. For example: child-src https://youtube.com would enable
     * embedding videos from YouTube but not from other origins. Use this in place of the deprecated frame-src
     * directive.
     *
     * @param string[] $childSources
     */
    public function setChildSources(array $childSources): void
    {
        $this->headers['child-src'] = $childSources;
    }

    public function addChildSource(string $source): void
    {
        $this->addSource('child-src', $source);
    }

    /**
     * Specifies the sources that can embed the current page. This directive applies to <frame>, <iframe>, <embed>,
     * and <applet> tags. This directive cant be used in <meta> tags and applies only to non-HTML resources.
     *
     * @param string[] $frameAncestors
     */
    public function setFrameAncestors(array $frameAncestors): void
    {
        $this->headers['frame-ancestors'] = $frameAncestors;
    }

    public function addFrameAncestor(string $frameAncestor): void
    {
        $this->addSource('frame-ancestors', $frameAncestor);
    }

    /**
     * Specifies the origins that can serve web fonts. Google’s Web Fonts could be enabled via the directive
     * font-src https://themes.googleusercontent.com.
     *
     * @param string[] $fontSources
     */
    public function setFontSources(array $fontSources): void
    {
        $this->headers['font-src'] = $fontSources;
    }

    public function addFontSource(string $source): void
    {
        $this->addSource('font-src', $source);
    }

    /**
     * Limits the origins to which you can connect (via XHR, WebSockets, and EventSource).
     *
     * @param string[] $connectSources
     */
    public function setConnectSources(array $connectSources): void
    {
        $this->headers['connect-src'] = $connectSources;
    }

    public function addConnectSource(string $source): void
    {
        $this->addSource('connect-src', $source);
    }

    /**
     * Lists valid endpoints for submission from <form> tags.
     *
     * @param string[] $formActionSources
     */
    public function setFormActionSources(array $formActionSources): void
    {
        $this->headers['form-action'] = $formActionSources;
    }

    public function addFormActionSource(string $source): void
    {
        $this->addSource('form-action', $source);
    }

    /**
     * Limits the kinds of plugins a page may invoke.
     *
     * @param string[] $pluginTypes
     */
    public function setPluginTypes(array $pluginTypes): void
    {
        $this->headers['plugin-types'] = $pluginTypes;
    }

    public function addPluginType(string $pluginType): void
    {
        $this->addSource('plugin-types', $pluginType);
    }

    /**
     * Restricts the URLs that can appear in a page’s <base> element.
     *
     * @param string[] $baseUri
     */
    public function setBaseUri(array $baseUri): void
    {
        $this->headers['base-uri'] = $baseUri;
    }

    public function addBaseUri(string $baseUri): void
    {
        $this->addSource('base-uri', $baseUri);
    }

    /**
     * Manually set any source type. Helpful for any source type that may be missing from this class or any iterative
     * processing of a subset of source types.
     *
     * @param string $sourceType
     * @param string[] $sources
     */
    public function setSources(string $sourceType, array $sources): void
    {
        $this->headers[$sourceType] = $sources;
    }

    private function addSource(string $sourceType, string $source): void
    {
        $this->headers[$sourceType] = array_merge($this->headers[$sourceType] ?? [], [$source]);
    }

    /**
     * Places restrictions on actions the page can take, rather than on resources that the page can load. If the sandbox
     * directive is present, the page will be treated as though it was loaded inside an iframe with a sandbox
     * attribute.
     *
     * @param string[] $sandbox
     */
    public function setSandbox(array $sandbox): void
    {
        $this->headers['sandbox'] = $sandbox;
    }

    public function addSandbox(string $sandbox): void
    {
        $this->addSource('sandbox', $sandbox);
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
    public function setReportOnly(bool $reportOnly): void
    {
        $this->reportOnly = $reportOnly;
    }

    /**
     * @param string $reportUri
     */
    public function setReportUri(string $reportUri): void
    {
        $this->reportUri = $reportUri;
    }

    /**
     * @param bool $compatible
     */
    public function setCompatible(bool $compatible): void
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
}
