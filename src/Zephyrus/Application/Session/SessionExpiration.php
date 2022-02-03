<?php namespace Zephyrus\Application\Session;

use InvalidArgumentException;
use RangeException;
use Zephyrus\Exceptions\SessionException;

class SessionExpiration
{
    /**
     * No automatic refresh algorithm will be used. Would need manual refreshing if needed by the application. To ensure
     * compatibility, this is the default configuration.
     */
    public const MODE_NONE = 'none';

    /**
     * Refresh the session identifier based on a probability check. This value range from 0 to 100 and represent a
     * percentage of chance that the session identifier should be refreshed. Meaning a value of 60 have 60% chance to
     * refresh the identifier on each request. Value of 0 would be ignored making it similar to the none mode. Value
     * of 100 would mean each request generate a new session id.
     */
    public const MODE_PROBABILITY = 'probability';

    /**
     * Refresh the session after x seconds defined in the refresh rate. During each request, the class will check if
     * the number of seconds allowed before a refresh has passed. Value of 120 would mean to refresh the session id each
     * two minutes.
     */
    public const MODE_INTERVAL = 'interval';

    /**
     * Refresh the session after x number of requests by the client. Keeps a counter of the request the associated
     * client has made and check if it needs to refresh the id. Value of 10 would mean to refresh the session after the
     * 10th request made by the client. Not that reliable for situations involving many background AJAX queries that
     * would alter the count.
     */
    public const MODE_REQUEST = 'request';

    /**
     * Defines the refresh mode to be used for the session. Can either be none, probability, interval or request. Each
     * mode will consider the refresh rate differently. See constants for more information.
     *
     * @var string
     */
    private string $refreshMode;

    /**
     * Defines the refresh rate value associated with the configured mode. Determines when the session identifier should
     * be refreshed for security purposes.
     *
     * @var int
     */
    private int $refreshRate;

    public function __construct(string $refreshMode, int $refreshRate)
    {
        $this->initializeRefreshMode($refreshMode);
        $this->initializeRefreshRate($refreshRate);
    }

    /**
     * Initiates expiration policies for the current session based on automated
     * refreshes after nth requests and/or after a certain time interval.
     */
    public function start()
    {
        $this->setupRefreshOnIntervalHandler();
        $this->setupRefreshOnNthRequestsHandler();
    }

    /**
     * Determines if the session needs to be refreshed either because the maximum number of allowed requests has been
     * reached, the timeout has finished or the probability matches.
     *
     * @return bool
     */
    public function isObsolete(): bool
    {
        return $this->isRefreshNeededByProbability()
            || $this->isRefreshNeededByRequest()
            || $this->isRefreshNeededByInterval();
    }

    public function getRefreshMode(): string
    {
        return $this->refreshMode;
    }

    public function getRefreshRate(): int
    {
        return $this->refreshRate;
    }

    private function isRefreshNeededByInterval(): bool
    {
        if ($this->refreshMode == self::MODE_INTERVAL && isset($_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'])) {
            $timeDifference = time() - $_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'];
            if ($timeDifference >= $this->refreshRate) {
                return true;
            }
        }
        return false;
    }

    private function isRefreshNeededByRequest(): bool
    {
        if ($this->refreshMode == self::MODE_REQUEST && isset($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
            if ($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'] <= 1) {
                $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'] = $this->refreshRate;
                return true;
            }
            $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH']--;
        }
        return false;
    }

    /**
     * Determines if the probability test of session refresh succeeded according to the desired percent.
     *
     * @return bool
     */
    private function isRefreshNeededByProbability(): bool
    {
        if ($this->refreshMode == self::MODE_PROBABILITY) {
            if ($this->refreshRate == 0) {
                return false;
            }
            $rand = ((float) mt_rand() / (float) mt_getrandmax()) * 100;
            if ($this->refreshRate == 100 || $rand <= $this->refreshRate) {
                return true;
            }
        }
        return false;
    }

    private function setupRefreshOnNthRequestsHandler()
    {
        if ($this->refreshMode != self::MODE_REQUEST) {
            // @codeCoverageIgnoreStart
            if (isset($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
                unset($_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH']);
            }
            return;
            // @codeCoverageIgnoreEnd
        }
        if (!isset($this->content['__HANDLER_REQUESTS_BEFORE_REFRESH'])) {
            $_SESSION['__HANDLER_REQUESTS_BEFORE_REFRESH'] = $this->refreshRate + 1;
        }
    }

    private function setupRefreshOnIntervalHandler()
    {
        if ($this->refreshMode != self::MODE_INTERVAL) {
            // @codeCoverageIgnoreStart
            if (isset($_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'])) {
                unset($_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP']);
            }
            return;
            // @codeCoverageIgnoreEnd
        }
        if (!isset($_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'])) {
            $_SESSION['__HANDLER_LAST_ACTIVITY_TIMESTAMP'] = time();
        }
    }

    /**
     * @param string $refreshMode
     * @throws SessionException
     */
    private function initializeRefreshMode(string $refreshMode)
    {
        if (!in_array($refreshMode, ['none', 'probability', 'interval', 'request'])) {
            throw new SessionException(SessionException::ERROR_INVALID_REFRESH_MODE);
        }
        $this->refreshMode = $refreshMode;
    }

    /**
     * @param int $refreshRate
     */
    private function initializeRefreshRate(int $refreshRate)
    {
        if ($refreshRate < 0) {
            throw new InvalidArgumentException("Session refresh rate must be positive.");
        }
        if ($this->refreshMode == self::MODE_PROBABILITY && $refreshRate > 100) {
            throw new RangeException("Session refresh rate must be between 0 and 100 (percentage) for probability mode.");
        }
        $this->refreshRate = $refreshRate;
    }
}
