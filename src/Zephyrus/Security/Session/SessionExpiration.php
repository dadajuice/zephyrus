<?php

namespace Zephyrus\Security\Session;

use Zephyrus\Application\SessionStorage;

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

    /**
     * @var array
     */
    private $content = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->refreshAfterNthRequests = $config['refresh_after_requests'] ?? null;
        $this->refreshAfterInterval = $config['refresh_after_interval'] ?? null;
        $this->refreshProbability = null;
        if (isset($config['refresh_probability'])) {
            $this->setRefreshProbability($config['refresh_probability']);
        }
    }

    public function start(SessionStorage $storage)
    {
        $this->content = &$storage->getContent();
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
            $this->content['__HANDLER_REQUESTS_BEFORE_REFRESH'] = $this->refreshAfterNthRequests;
        }
        if (!empty($this->refreshAfterInterval)) {
            $this->content['__HANDLER_SECONDS_BEFORE_REFRESH'] = $this->refreshAfterInterval;
            $this->content['__HANDLER_LAST_ACTIVITY_TIMESTAMP'] = time();
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
        if (isset($this->content['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
            if ($this->content['__HANDLER_REQUESTS_BEFORE_REFRESH'] <= 1) {
                return true;
            }
            $this->content['__HANDLER_REQUESTS_BEFORE_REFRESH']--;
        }
        if (isset($this->content['__HANDLER_SECONDS_BEFORE_REFRESH'])) {
            $timeDifference = time() - $this->content['__HANDLER_LAST_ACTIVITY_TIMESTAMP'];
            if ($timeDifference >= $this->content['__HANDLER_SECONDS_BEFORE_REFRESH']) {
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
        if (empty($this->refreshAfterNthRequests) && isset($this->content['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
            unset($this->content['__HANDLER_REQUESTS_BEFORE_REFRESH']);
        } elseif (!isset($this->content['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
            $this->content['__HANDLER_REQUESTS_BEFORE_REFRESH'] = $this->refreshAfterNthRequests;
        }
    }

    private function setupRefreshOnIntervalHandler()
    {
        if (empty($this->refreshAfterInterval)) {
            if (isset($this->content['__HANDLER_SECONDS_BEFORE_REFRESH'])) {
                unset($this->content['__HANDLER_SECONDS_BEFORE_REFRESH']);
            }
            if (isset($this->content['__HANDLER_LAST_ACTIVITY_TIMESTAMP'])) {
                unset($this->content['__HANDLER_LAST_ACTIVITY_TIMESTAMP']);
            }
        } elseif (!isset($this->content['__HANDLER_SECONDS_BEFORE_REFRESH'])) {
            $this->content['__HANDLER_SECONDS_BEFORE_REFRESH'] = $this->refreshAfterInterval;
            $this->content['__HANDLER_LAST_ACTIVITY_TIMESTAMP'] = time();
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
     *
     * @throws \RangeException
     */
    public function setRefreshProbability(float $refreshProbability)
    {
        if ($refreshProbability < 0 || $refreshProbability > 1) {
            throw new \RangeException('Probability must be between 0 and 1');
        }
        $this->refreshProbability = $refreshProbability;
    }
}
