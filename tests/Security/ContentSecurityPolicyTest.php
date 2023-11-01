<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Security\ContentSecurityPolicy;

class ContentSecurityPolicyTest extends TestCase
{
    public function testSimpleHeaders()
    {
        $csp = new ContentSecurityPolicy();
        $csp->setDefaultSources(["'self'"]);
        $csp->setFontSources(["'self'", 'https://fonts.googleapis.com', 'https://fonts.gstatic.com']);
        $csp->setStyleSources(["'self'", 'https://fonts.googleapis.com']);
        $csp->addStyleSource('https://toto.com');
        $csp->setScriptSources(["'self'", 'https://ajax.googleapis.com', 'https://maps.googleapis.com', 'https://www.google-analytics.com', 'http://connect.facebook.net']);
        $csp->setChildSources(["'self'", 'http://staticxx.facebook.com']);
        $csp->setImageSources(["'self'", 'data:', 'https://csi.gstatic.com']);
        $csp->send();
        $headers = xdebug_get_headers();
        $nonce = ContentSecurityPolicy::getRequestNonce();
        $result = <<<EOT
Content-Security-Policy: default-src 'self';script-src 'self' https://ajax.googleapis.com https://maps.googleapis.com https://www.google-analytics.com http://connect.facebook.net 'nonce-$nonce';style-src 'self' https://fonts.googleapis.com https://toto.com;font-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com;img-src 'self' data: https://csi.gstatic.com;child-src 'self' http://staticxx.facebook.com;
EOT;
        var_dump($headers);
        self::assertTrue(in_array($result, $headers));
    }

    public function testNonce()
    {
        $nonce = ContentSecurityPolicy::getRequestNonce();
        $nonce2 = nonce();
        self::assertEquals($nonce, $nonce2);
    }

    public function testHeaders()
    {
        $csp = new ContentSecurityPolicy();
        $csp->setDefaultSources(["'self'"]);
        $csp->setConnectSources(['http://test.local']);
        $csp->setFormActionSources(['http://test.local']);
        $csp->setFrameAncestors(['http://test.local']);
        $csp->setMediaSources(['http://test.local']);
        $csp->setPluginTypes(['http://test.local']);
        $csp->setBaseUri(['http://test.local']);
        $csp->setWorkerSources(['http://test.local']);
        $csp->setObjectSources(['http://test.local']);
        $csp->setSandbox(['http://test.local']);
        $csp->setCompatible(true);
        $csp->setReportOnly(true);
        self::assertTrue($csp->isOnlyReporting());
        $csp->setReportUri('http://test.local/report');
        $res = $csp->getAllHeader();
        self::assertEquals("'self'", $res['default-src'][0]);
        $csp->send();
        $headers = xdebug_get_headers();
        $result = <<<EOT
X-Content-Security-Policy-Report-Only: default-src 'self';worker-src http://test.local;base-uri http://test.local;connect-src http://test.local;form-action http://test.local;frame-ancestors http://test.local;media-src http://test.local;object-src http://test.local;plugin-types http://test.local;sandbox http://test.local;report-uri http://test.local/report;
EOT;
        self::assertTrue(in_array($result, $headers));
    }

    public function testAddHeaders()
    {
        $csp = new ContentSecurityPolicy();
        $csp->addDefaultSource(ContentSecurityPolicy::SELF);
        $csp->addConnectSource('http://test.local');
        $csp->addFormActionSource('http://test.local');
        $csp->addFrameAncestor('http://test.local');
        $csp->addMediaSource('http://test.local');
        $csp->addPluginType('http://test.local');
        $csp->addBaseUri('http://test.local');
        $csp->addWorkerSource('http://test.local');
        $csp->addObjectSource('http://test.local');
        $csp->addSandbox('http://test.local');
        $csp->setCompatible(true);
        $csp->setReportOnly(true);
        self::assertTrue($csp->isOnlyReporting());
        $csp->setReportUri('http://test.local/report');
        $res = $csp->getAllHeader();
        self::assertEquals("'self'", $res['default-src'][0]);
        $csp->send();
        $headers = xdebug_get_headers();
        $result = <<<EOT
X-Content-Security-Policy-Report-Only: default-src 'self';worker-src http://test.local;base-uri http://test.local;connect-src http://test.local;form-action http://test.local;frame-ancestors http://test.local;media-src http://test.local;object-src http://test.local;plugin-types http://test.local;sandbox http://test.local;report-uri http://test.local/report;
EOT;
        self::assertTrue(in_array($result, $headers));
    }

    public function testCustomHeaders()
    {
        $csp = new ContentSecurityPolicy();
        $csp->setSources('zephy-sources', ["http://test.local"]);
        $csp->send();
        $headers = xdebug_get_headers();
        $result = <<<EOT
Content-Security-Policy: zephy-sources http://test.local;
EOT;
        self::assertTrue(in_array($result, $headers));
    }
}