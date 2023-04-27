<?php namespace Zephyrus\Application;

class Feedback
{
    public static function error(array $fieldErrors)
    {
        self::addFeedback('ERROR', $fieldErrors);
    }

    public static function readAll(): array
    {
        $feedback = Session::getInstance()->read('__FEEDBACK');
        $args = [];
        $args["error"] = $feedback['ERROR'] ?? [];
        self::clearAll();
        return $args;
    }

    public static function clearAll()
    {
        Session::getInstance()->remove('__FEEDBACK');
    }

    private static function addFeedback(string $type, array $fieldErrors)
    {
        $feedback = Session::getInstance()->read('__FEEDBACK');
        if (is_null($feedback)) {
            $feedback = [];
        }
        $feedback[$type] = $fieldErrors;
        Session::getInstance()->set('__FEEDBACK', $feedback);
    }
}
