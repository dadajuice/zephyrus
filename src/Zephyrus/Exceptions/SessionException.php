<?php namespace Zephyrus\Exceptions;

use Exception;

class SessionException extends Exception
{
    public const ERROR_UNSECURE_CONFIGURATIONS = 901;
    public const ERROR_INVALID_FINGERPRINT = 902;
    public const ERROR_INVALID_LIFETIME = 903;
    public const ERROR_INVALID_LIFETIME_MODE = 904;
    public const ERROR_SAVE_PATH_NOT_WRITABLE = 905;
    public const ERROR_SAVE_PATH_NOT_EXIST = 906;
    public const ERROR_INVALID_REFRESH_RARE = 907;
    public const ERROR_INVALID_REFRESH_MODE = 908;

    public function __construct(int $code)
    {
        parent::__construct($this->codeToMessage($code), $code);
    }

    private function codeToMessage(int $code): string
    {
        return match ($code) {
            self::ERROR_UNSECURE_CONFIGURATIONS => "Session configurations are not secure. Fixation may be possible. Please review your php.ini or local settings (eg. .htaccess) for directive session.use_cookies and session.use_only_cookies.",
            self::ERROR_INVALID_FINGERPRINT => "Session fingerprint is invalid based on configurations and thus cannot start the session.",
            self::ERROR_INVALID_LIFETIME => "Session lifetime configuration property must be int (seconds before expiration).",
            self::ERROR_INVALID_LIFETIME_MODE => "Session lifetime mode configuration property must either 'default' or 'reset'.",
            self::ERROR_SAVE_PATH_NOT_WRITABLE => "The specified session save path is not writable.",
            self::ERROR_SAVE_PATH_NOT_EXIST => "The specified session save path doesn't exist.",
            self::ERROR_INVALID_REFRESH_RARE => "Session refresh rate configuration property must be int.",
            self::ERROR_INVALID_REFRESH_MODE => "The specified session refresh mode is invalid. Must be one of the following values 'none', 'probability', 'interval' or 'request'.",
            default => "Unknown session error.",
        };
    }
}
