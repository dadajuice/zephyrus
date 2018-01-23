<?php namespace Zephyrus\Security\Session;

class SessionExpiration
{
    /**
     * @var int number of requests before automatically refreshing the identifier
     */
    private $refreshAfterNthRequests;

    /**
     * @var int number of seconds before automatically refreshing the identifier
     */
    private $refreshAfterInterval;

    /**
     * @var float probability (in float percent 0-1) of a random identifier refresh
     */
    private $refreshProbability;

    public function __construct($refreshAfterNthRequests, $refreshAfterInterval, $refreshProbability)
    {
        $this->refreshAfterNthRequests = $refreshAfterNthRequests ?? null;
        $this->refreshAfterInterval = $refreshAfterInterval ?? null;
        $this->refreshProbability = null;
        if ($refreshProbability) {
            $this->setRefreshProbability($refreshProbability);
        }
    }

    public function start()
    {
        $this->setupRefreshOnNthRequestsHandler();
        $this->setupRefreshOnIntervalHandler();
    }

    /**
     * Initiates expiration policies for the current session based on automated
     * refreshes after nth requests and/or after a certain time interval.
     */
    public function initiateExpiration()
    {
        if (!empty($this->refreshAfterNthRequests)) {
            $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'] = $this->refreshAfterNthRequests;
        }
        if (!empty($this->refreshAfterInterval)) {
            $_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH'] = $this->refreshAfterInterval;
            $_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'] = time();
        }
    }

    /**
     * Determines if the session needs to be refreshed either because the
     * maximum number of allowed requests has been reached or the timeout has
     * finished.
     *
     * @return bool
     */
    public function isObsolete()
    {
        if ($this->refreshAfterNthRequests == 1 || $this->isRefreshNeededByProbability()) {
            return true;
        }
        if (isset($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
            if ($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'] <= 1) {
                return true;
            }
            $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH']--;
        }
        if (isset($_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH'])) {
            $timeDifference = time() - $_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'];
            if ($timeDifference >= $_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determines if the probability test of session refresh succeeded
     * according to the desired percent.
     *
     * @return bool
     */
    private function isRefreshNeededByProbability()
    {
        if (is_null($this->refreshProbability)) {
            return false;
        }
        $rand = (float) mt_rand() / (float) mt_getrandmax();
        if ($this->refreshProbability == 1.0 || $rand <= $this->refreshProbability) {
            return true;
        }
        return false;
    }

    private function setupRefreshOnNthRequestsHandler()
    {
        if (empty($this->refreshAfterNthRequests) && isset($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
            unset($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH']);
        } elseif (!isset($this->content['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
            $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'] = $this->refreshAfterNthRequests;
        }
    }

    private function setupRefreshOnIntervalHandler()
    {
        if (empty($this->refreshAfterInterval)) {
            if (isset($_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH'])) {
                unset($_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH']);
            }
            if (isset($_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'])) {
                unset($_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP']);
            }
        } elseif (!isset($_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH'])) {
            $_SESSION['__HANDLER_SECONDS_BEFORE_REFRESH'] = $this->refreshAfterInterval;
            $_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'] = time();
        }
    }

    /**
     * @return int
     */
    public function getRefreshAfterNthRequests(): ?int
    {
        return $this->refreshAfterNthRequests;
    }

    /**
     * @param int $nthRequests
     */
    public function setRefreshAfterNthRequests(int $nthRequests)
    {
        $this->refreshAfterNthRequests = $nthRequests;
    }

    /**
     * @return int
     */
    public function getRefreshAfterInterval(): ?int
    {
        return $this->refreshAfterInterval;
    }

    /**
     * @param int $refreshAfterInterval
     */
    public function setRefreshAfterInterval(int $refreshAfterInterval)
    {
        $this->refreshAfterInterval = $refreshAfterInterval;
    }

    /**
     * @return float
     */
    public function getRefreshProbability(): ?float
    {
        return $this->refreshProbability;
    }

    /**
     * @param float $refreshProbability
     * @throws \RangeException
     */
    public function setRefreshProbability(float $refreshProbability)
    {
        if ($refreshProbability < 0 || $refreshProbability > 1) {
            throw new \RangeException("Probability must be between 0 and 1");
        }
        $this->refreshProbability = $refreshProbability;
    }
}
