<?php namespace Zephyrus\Security;

use RuntimeException;
use Zephyrus\Application\Configuration;
use Zephyrus\Exceptions\IntrusionDetectionException;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Security\IntrusionDetection\IntrusionCache;
use Zephyrus\Security\IntrusionDetection\IntrusionMonitor;
use Zephyrus\Security\IntrusionDetection\IntrusionReport;
use Zephyrus\Security\IntrusionDetection\IntrusionRuleLoader;

class IntrusionDetection
{
    public const DEFAULT_CONFIGURATIONS = [
        'enabled' => true, // Enable the intrusion detection feature
        'cached' => true, // Enable the APCu (PHP cache) for loaded rules
        'custom_file' => '', // Change the default rule file
        'impact_threshold' => 0, // Minimum impact to be considered to throw an exception (default is any detection)
        'monitor_cookies' => true, // Verifies the content of request cookies
        'exceptions' => [] // List of request parameters to be exempt of detection (e.g. '__utmz')
    ];

    private array $configurations;

    /**
     * @var IntrusionMonitor
     */
    private IntrusionMonitor $monitor;

    /**
     * @var array
     */
    private array $exceptions = [];

    /**
     * @var int
     */
    private int $impactThreshold = 0;

    /**
     * @var bool
     */
    private bool $includeCookiesMonitoring = true;

    public function __construct(array $configurations = [])
    {
        $this->initializeConfigurations($configurations);
        $this->initializeMonitor();
        $this->initializeExceptions();
        $this->initializeImpactThreshold();
        $this->initializeCookieMonitoring();
    }

    /**
     * Execute the intrusion detection analysis using the specified monitored inputs. If an intrusion is detected, the
     * method will throw an exception.
     *
     * @throws IntrusionDetectionException
     */
    public function run(): IntrusionReport
    {
        $this->monitor->setExceptions($this->exceptions);
        $report = $this->monitor->run($this->getMonitoringInputs());
        if ($report->getImpact() > $this->impactThreshold) {
            throw new IntrusionDetectionException($report);
        }
        return $report;
    }

    private function initializeConfigurations(array $configurations)
    {
        if (empty($configurations)) {
            $configurations = Configuration::getConfiguration('ids') ?? self::DEFAULT_CONFIGURATIONS;
        }
        $this->configurations = $configurations;
    }

    private function initializeMonitor()
    {
        $loader = new IntrusionRuleLoader($this->configurations['custom_file'] ?? null);
        if (isset($this->configurations['cached']) && $this->configurations['cached']) {
            $cache = new IntrusionCache();
            $intrusionRules = $cache->getRules();
            if (empty($intrusionRules)) {
                $intrusionRules = $loader->loadFromFile();
                $cache->cache($intrusionRules);
            }
        } else {
            $intrusionRules = $loader->loadFromFile();
        }
        $this->monitor = new IntrusionMonitor($intrusionRules);
    }

    private function initializeExceptions()
    {
        if (isset($this->configurations['exceptions']) && !empty($this->configurations['exceptions'])) {
            $this->exceptions = $this->configurations['exceptions'];
        }
    }

    private function initializeCookieMonitoring()
    {
        if (isset($this->configurations['monitor_cookies']) && $this->configurations['monitor_cookies']) {
            $this->includeCookiesMonitoring = $this->configurations['monitor_cookies'];
        }
    }

    private function initializeImpactThreshold()
    {
        if (isset($this->configurations['impact_threshold'])) {
            if (!is_int($this->configurations['impact_threshold'])) {
                throw new RuntimeException("IDS impact threshold configuration property must be int.");
            }
            $this->impactThreshold = $this->configurations['impact_threshold'];
        }
    }

    /**
     * Prepares the request parameters to be verified by the IDS monitor. Will automatically include all request data
     * and cookies if included in configurations.
     *
     * @return array
     */
    private function getMonitoringInputs(): array
    {
        $request = RequestFactory::read();
        $guard = $request->getParameters();
        if ($this->includeCookiesMonitoring) {
            $guard = array_merge($guard, $request->getCookies());
        }
        return $guard;
    }
}
