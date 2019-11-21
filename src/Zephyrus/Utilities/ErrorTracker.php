<?php namespace Models;

use Exception;
use stdClass;
use Zephyrus\Application\Session;
use Zephyrus\Database\Broker;
use Zephyrus\Network\RequestFactory;

class ErrorTracker
{
    private const SESSION_KEY = "__ZEPH_ERROR_TRACKER";

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @var array
     */
    private $trackedErrors = [];

    /**
     * @var string | null
     */
    private $persistencePath = null;

    public static function getInstance(?string $persistencePath = null): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($persistencePath);
        }
        return self::$instance;
    }

    public function getTrackedErrors()
    {
        return $this->trackedErrors;
    }

    public function clean()
    {
        $this->trackedErrors = [];
        $this->save();
    }

    public function addFromException(Exception $exception, $type = "Uncaught Exception", array $additionalInformation = [])
    {
        $this->add($this->buildErrorObject(
            $type,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTrace(),
            $additionalInformation));
    }

    public function add(stdClass $object)
    {
        $this->trackedErrors[] = $object;
        $this->trackedErrors = array_reverse($this->trackedErrors);
        $this->save();
    }

    public function buildErrorObject(string $type, string $message, string $file, int $line, array $stackTrace = [], array $additionalInformation = [])
    {
        $request = RequestFactory::read();
        return (object) [
            'type' => $type,
            'message' => $message,
            'date_time' => date(Broker::SQL_FORMAT_DATE_TIME),
            'request' => [
                'parameters' => $request->getParameters(),
                'method' => $request->getMethod(),
                'clientIp' => $request->getClientIp(),
                'requestedUri' => $request->getRequestedUri(),
                'files' => $request->getFiles(),
                'cookies' => $request->getCookies()
            ],
            'file' => $file,
            'line' => $line,
            'stack' => $stackTrace,
            'additional_information' => $additionalInformation
        ];
    }

    private function save()
    {
        if (is_null($this->persistencePath)) {
            $this->saveToSession();
        } else {
            $this->saveToPersistence();
        }
    }

    private function __construct(?string $persistencePath = null)
    {
        if (is_null($persistencePath)) {
            $this->initializeFromSession();
        } else {
            $this->persistencePath = $persistencePath;
            $this->initializeFromPersistence();
        }
    }

    private function initializeFromSession()
    {
        $this->trackedErrors = Session::getInstance()->read(self::SESSION_KEY, []);
    }

    private function initializeFromPersistence()
    {
        if (!file_exists($this->persistencePath)) {

            $this->trackedErrors = [];
            return;
        }
        $this->verifyPersistence();
        $string = file_get_contents($this->persistencePath);
        $this->trackedErrors = json_decode($string, true);
        $jsonLastError = json_last_error();
        if ($jsonLastError > JSON_ERROR_NONE) {
            throw new Exception("Cannot properly decode JSON errors from given persistence file");
        }
    }

    private function saveToSession()
    {
        Session::getInstance()->set(self::SESSION_KEY, $this->trackedErrors);
    }

    private function saveToPersistence()
    {
        if (!file_exists($this->persistencePath)) {
            file_put_contents($this->persistencePath, "{}");
        }
        $this->verifyPersistence();
        $json = json_encode($this->trackedErrors, JSON_PRETTY_PRINT);
        $jsonLastError = json_last_error();
        if ($jsonLastError > JSON_ERROR_NONE) {
            throw new Exception("Cannot properly encode JSON errors");
        }
        file_put_contents($this->persistencePath, $json);
    }

    private function verifyPersistence()
    {
        if (!is_readable($this->persistencePath)) {
            throw new Exception("File {$this->persistencePath} exists, but does not appear to be 
                readable. Make sure to give appropriate read and write access.");
        }
        if (!is_writable($this->persistencePath)) {
            throw new Exception("File {$this->persistencePath} exists, but does not appear to be 
                writable. Make sure to give appropriate read and write access.");
        }
    }
}
