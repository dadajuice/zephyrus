<?php namespace Zephyrus\Application;

use stdClass;

class Feedback
{
    private const SESSION_KEY = '__ZF_FEEDBACK';

    public static function error(array $fieldErrors): void
    {
        self::addFeedback('ERROR', $fieldErrors);
    }

    public static function hasError(string $field): bool
    {
        $feedback = Session::getInstance()->read(self::SESSION_KEY);
        if (is_null($feedback)) {
            return false;
        }
        return key_exists($field, $feedback['ERROR']);
    }

    public static function readError(string $field): array
    {
        $feedback = Session::getInstance()->read(self::SESSION_KEY);
        if (is_null($feedback)) {
            return [];
        }
        return $feedback['ERROR'][$field] ?? [];
    }

    public static function readAll(): stdClass
    {
        $feedback = Session::getInstance()->read(self::SESSION_KEY);
        $args = [];
        $args["error"] = $feedback['ERROR'] ?? [];
        return (object) $args;
    }

    public static function clearAll(): void
    {
        Session::getInstance()->remove(self::SESSION_KEY);
    }

    private static function addFeedback(string $type, array $fieldErrors): void
    {
        $feedback = Session::getInstance()->read(self::SESSION_KEY);
        if (is_null($feedback)) {
            $feedback = [];
        }
        $feedback[$type] = $fieldErrors;
        Session::getInstance()->set(self::SESSION_KEY, $feedback);
    }
}
