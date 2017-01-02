<?php namespace Zephyrus\Security;

use Expose\FilterCollection;
use Expose\Manager;
use Expose\Report;

class IntrusionDetection
{
    const REQUEST = 1;
    const GET = 2;
    const POST = 4;
    const COOKIE = 8;

    /**
     * @var IntrusionDetection
     */
    private static $instance = null;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var int
     */
    private $surveillance = 0;

    /**
     * @var Callable
     */
    private $detectionCallback = null;

    /**
     * Obtain the single allowed instance for IntrusionDetection through
     * singleton pattern method.
     *
     * @param mixed[] | null $config
     * @return IntrusionDetection
     */
    public static function getInstance($config = null)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Execute the intrusion detection analysis using the specified monitored
     * inputs. If an intrusion is detected, the method will launch the detection
     * callback.
     *
     * @param callable | null $detectionCallback (optional)
     * @throws \Exception
     */
    public function run(Callable $detectionCallback = null)
    {
        if (!is_null($detectionCallback)) {
            $this->detectionCallback = $detectionCallback;
        }
        $guard = $this->getMonitoringInputs();
        if (empty($guard)) {
            throw new \Exception("Nothing to monitor ! Either configure the IDS to monitor at least one input or completely deactivate this feature.");
        }

        $this->manager->run($guard);
        if ($this->manager->getImpact() > 0) {
            $data = $this->getDetectionData($this->manager->getReports());
            if (!is_null($this->detectionCallback)) {
                $callback = $this->detectionCallback;
                $callback($data);
            } else {
                throw new \Exception("No intrusion detection callback defined");
            }
        }
    }

    /**
     * Applies the method to launch when an instruction has been detected by
     * PHPIDS. Will pass the detection result to the callback.
     *
     * @param callable $callback
     */
    public function onDetection(Callable $callback)
    {
        $this->detectionCallback = $callback;
    }

    /**
     * Applies the desired inputs to be analysed with bitwise operations using
     * the class constants (e.g. GET | POST | COOKIE). Default to GET and POST
     * only.
     *
     * @param int $surveillanceBitwise
     */
    public function setSurveillance($surveillanceBitwise)
    {
        $this->surveillance = $surveillanceBitwise;
    }

    /**
     * @return bool
     */
    public function isMonitoringRequest()
    {
        return ($this->surveillance & self::REQUEST) > 0;
    }

    /**
     * @return bool
     */
    public function isMonitoringGet()
    {
        return ($this->surveillance & self::GET) > 0;
    }

    /**
     * @return bool
     */
    public function isMonitoringPost()
    {
        return ($this->surveillance & self::POST) > 0;
    }

    /**
     * @return int
     */
    public function isMonitoringCookie()
    {
        return ($this->surveillance & self::COOKIE) > 0;
    }

    /**
     * Private constructor which initiates the configuration of the PHPIDS
     * framework and prepare the monitor component.
     */
    private function __construct()
    {
        $filters = new FilterCollection();
        $filters->load();
        $this->manager = new Manager($filters, SystemLog::getSecurityLogger());
        $this->surveillance = self::GET | self::POST;
    }

    /**
     * Retrieves the monitoring inputs to consider depending on the current
     * configuration.
     *
     * @return mixed[]
     */
    private function getMonitoringInputs()
    {
        $guard = [];
        if ($this->surveillance & self::REQUEST) {
            $guard['REQUEST'] = $_REQUEST;
        }
        if ($this->surveillance & self::GET) {
            $guard['GET'] = $_GET;
        }
        if ($this->surveillance & self::POST) {
            $guard['POST'] = $_POST;
        }
        if ($this->surveillance & self::COOKIE) {
            $guard['COOKIE'] = $_COOKIE;
        }
        return $guard;
    }

    /**
     * Constructs a custom basic associative array based on the PHPIDS report
     * when an intrusion is detected. Will contains essential data such as
     * impact, targeted inputs and detection descriptions.
     *
     * @param Report[] $reports
     * @return mixed[]
     */
    private function getDetectionData($reports)
    {
        $data = [
            'impact' => 0,
            'detections' => []
        ];
        foreach ($reports as $report) {
            $variableName = $report->getVarName();
            $filters = $report->getFilterMatch();
            if (!isset($data['detections'][$variableName])) {
                $data['detections'][$variableName] = [
                    'value' => $report->getVarValue(),
                    'events' => []
                ];
            }
            foreach ($filters as $filter) {
                $data['detections'][$variableName]['events'][] = [
                    'description' => $filter->getDescription(),
                    'impact' => $filter->getImpact()
                ];
                $data['impact'] += $filter->getImpact();
            }
        }
        return $data;
    }
}