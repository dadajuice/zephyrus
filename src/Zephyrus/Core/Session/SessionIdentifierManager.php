<?php namespace Zephyrus\Core\Session;

class SessionIdentifierManager
{
    private const SESSION_KEY_LAST_ACTIVITY = "__ZF_SESSION_LAST_ACTIVITY_TIMESTAMP";
    private const SESSION_KEY_REFRESH_COUNT = "__ZF_SESSION_REQUESTS_BEFORE_REFRESH";

    /**
     * No automatic refresh algorithm will be used. Would need manual refreshing by the application. To ensure
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
        $this->refreshMode = $refreshMode;
        $this->refreshRate = $refreshRate;
    }

    /**
     * Initiates expiration policies for the current session based on automated
     * refreshes after nth requests and/or after a certain time interval.
     */
    public function configure(): void
    {
        session_start();
        $this->setupRefreshOnInterval();
        $this->setupRefreshOnNthRequests();
    }

    /**
     * Retrieves the current session identifier.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return session_status() == PHP_SESSION_ACTIVE ? session_id() : null;
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
        if ($this->refreshMode == self::MODE_INTERVAL && isset($_SESSION[self::SESSION_KEY_LAST_ACTIVITY])) {
            $timeDifference = time() - $_SESSION[self::SESSION_KEY_LAST_ACTIVITY];
            if ($timeDifference >= $this->refreshRate) {
                return true;
            }
        }
        return false;
    }

    private function isRefreshNeededByRequest(): bool
    {
        if ($this->refreshMode == self::MODE_REQUEST && isset($_SESSION[self::SESSION_KEY_REFRESH_COUNT])) {
            if ($_SESSION[self::SESSION_KEY_REFRESH_COUNT] <= 1) {
                $_SESSION[self::SESSION_KEY_REFRESH_COUNT] = $this->refreshRate;
                return true;
            }
            $_SESSION[self::SESSION_KEY_REFRESH_COUNT]--;
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

    private function setupRefreshOnNthRequests(): void
    {
        if ($this->refreshMode != self::MODE_REQUEST) {
            // @codeCoverageIgnoreStart
            if (isset($_SESSION[self::SESSION_KEY_REFRESH_COUNT])) {
                unset($_SESSION[self::SESSION_KEY_REFRESH_COUNT]);
            }
            return;
            // @codeCoverageIgnoreEnd
        }
        if (!isset($_SESSION[self::SESSION_KEY_REFRESH_COUNT])) {
            $_SESSION[self::SESSION_KEY_REFRESH_COUNT] = $this->refreshRate + 1;
        }
    }

    private function setupRefreshOnInterval(): void
    {
        if ($this->refreshMode != self::MODE_INTERVAL) {
            // @codeCoverageIgnoreStart
            if (isset($_SESSION[self::SESSION_KEY_LAST_ACTIVITY])) {
                unset($_SESSION[self::SESSION_KEY_LAST_ACTIVITY]);
            }
            return;
            // @codeCoverageIgnoreEnd
        }
        if (!isset($_SESSION[self::SESSION_KEY_LAST_ACTIVITY])) {
            $_SESSION[self::SESSION_KEY_LAST_ACTIVITY] = time();
        }
    }
}
