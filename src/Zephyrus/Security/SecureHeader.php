<?php

namespace Zephyrus\Security;

class SecureHeader
{
    /**
     * Provides Clickjacking protection. Values: deny - no rendering within a frame,
     * sameorigin - no rendering if origin mismatch, allow-from: DOMAIN - allow
     * rendering if framed by frame loaded from DOMAIN.
     *
     * @var string
     */
    private $frameOptions = 'SAMEORIGIN';

    /**
     * This header enables the Cross-site scripting (XSS) filter built into most recent
     * web browsers. It's usually enabled by default anyway, so the role of this header
     * is to re-enable the filter for this particular website if it was disabled by
     * the user.
     *
     * @var string
     */
    private $xssProtection = '1; mode=block';

    /**
     * The only defined value, "nosniff", prevents Internet Explorer and Google Chrome
     * from MIME-sniffing a response away from the declared content-type. This reduces
     * exposure to drive-by download attacks and sites serving user uploaded content
     * that, by clever naming, could be treated by MSIE as executable or dynamic HTML
     * files.
     *
     * @var string
     */
    private $contentTypeOptions = 'nosniff';

    /**
     * HTTP Strict-Transport-Security (HSTS) enforces secure (HTTP over SSL/TLS) connections
     * to the server. This reduces impact of bugs in web applications leaking session data
     * through cookies and external links and defends against Man-in-the-middle attacks. HSTS
     * also disables the ability for user's to ignore SSL negotiation warnings.
     *
     * @var string
     */
    private $strictTransportSecurity = 'max-age=16070400; includeSubDomains';

    /**
     * Requires careful tuning and precise definition of the policy. If
     * enabled, CSP has significant impact on the way browser renders
     * pages (e.g., inline JavaScript disabled by default and must be
     * explicitly allowed in policy). CSP prevents a wide range of attacks,
     * including Cross-site scripting and other cross-site injections.
     *
     * @var ContentSecurityPolicy
     */
    private $contentSecurityPolicy = null;

    /**
     * Bulk send HTTP response header aiming security purposes. Each one are
     * explained in code.
     *
     * @see https://www.owasp.org/index.php/List_of_useful_HTTP_headers
     */
    public function send()
    {
        $this->sendContentOptions();
        $this->sendFrameOptions();
        $this->sendXssProtection();
        $this->sendStrictTransport();
        $this->sendContentSecurity();
    }

    /**
     * @return string
     */
    public function getFrameOptions(): string
    {
        return $this->frameOptions;
    }

    /**
     * @param string $frameOptions
     */
    public function setFrameOptions(string $frameOptions)
    {
        $this->frameOptions = $frameOptions;
    }

    /**
     * @return string
     */
    public function getXssProtection(): string
    {
        return $this->xssProtection;
    }

    /**
     * @param string $xssProtection
     */
    public function setXssProtection(string $xssProtection)
    {
        $this->xssProtection = $xssProtection;
    }

    /**
     * @return string
     */
    public function getContentTypeOptions(): string
    {
        return $this->contentTypeOptions;
    }

    /**
     * @param string $contentTypeOptions
     */
    public function setContentTypeOptions(string $contentTypeOptions)
    {
        $this->contentTypeOptions = $contentTypeOptions;
    }

    /**
     * @return string
     */
    public function getStrictTransportSecurity(): string
    {
        return $this->strictTransportSecurity;
    }

    /**
     * @param string $strict
     */
    public function setStrictTransportSecurity(string $strict)
    {
        $this->strictTransportSecurity = $strict;
    }

    /**
     * @return ContentSecurityPolicy
     */
    public function getContentSecurityPolicy(): ?ContentSecurityPolicy
    {
        return $this->contentSecurityPolicy;
    }

    /**
     * @param ContentSecurityPolicy $csp
     */
    public function setContentSecurityPolicy(?ContentSecurityPolicy $csp)
    {
        $this->contentSecurityPolicy = $csp;
    }

    private function sendContentOptions()
    {
        if (!empty($this->contentTypeOptions)) {
            header('X-Content-Type-Options: ' . $this->contentTypeOptions);
        }
    }

    private function sendFrameOptions()
    {
        if (!empty($this->frameOptions)) {
            header('X-Frame-Options: ' . $this->frameOptions);
        }
    }

    private function sendXssProtection()
    {
        if (!empty($this->xssProtection)) {
            header('X-XSS-Protection: ' . $this->xssProtection);
        }
    }

    private function sendStrictTransport()
    {
        if (!empty($this->strictTransportSecurity)) {
            header('Strict-Transport-Security: ' . $this->strictTransportSecurity);
        }
    }

    private function sendContentSecurity()
    {
        if (!is_null($this->contentSecurityPolicy)) {
            $this->contentSecurityPolicy->send();
        }
    }
}
