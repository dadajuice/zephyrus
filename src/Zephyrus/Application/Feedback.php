<?php namespace Zephyrus\Application;

class Feedback
{
    private const SESSION_KEY = '__ZF_FEEDBACK';

    /**
     * Registers the given error messages into the feedback session for later rendering. The given messages must be in
     * an associative array form where the keys are the field names (including complete pathing if available) and the
     * values are an array of error messages. E.g.
     *
     * 'firstname' => ['Must not be empty'],
     * 'amount' => ['Must be a number', 'Must be positive'],
     * 'cart[].quantity.2' => ['Must not be empty']
     *
     * @param array $fieldErrors
     */
    public static function register(array $fieldErrors): void
    {
        $feedback = self::getSavedFeedback();
        foreach ($fieldErrors as $registeredName => $errorMessages) {
            $registeredName = self::pathingToFieldName($registeredName);
            if (!isset($feedback[$registeredName])) {
                $feedback[$registeredName] = [];
            }
            $feedback[$registeredName] = array_merge($feedback[$registeredName], $errorMessages);
        }
        Session::getInstance()->set(self::SESSION_KEY, $feedback);
    }

    /**
     * Retrieves the error messages associated with the given field name. If there is no direct key matching the field
     * name, it will try to find key starting with the given name and join the messages. Useful to group pathing
     * errors. E.g.
     *
     * 'firstname' => ['Must not be empty'],
     * 'amount' => ['Must be a number', 'Must be positive'],
     * 'cart[].quantity.2' => ['Must not be empty'],
     * 'cart[].amount.2' => ['Must be a number']
     *
     * Feedback::read('firstname'); // ['Must not be empty']
     * Feedback::read('cart[]'); // ['Must not be empty', 'Must be a number']
     *
     * @param string $field
     * @return array
     */
    public static function read(string $field): array
    {
        $feedback = self::getSavedFeedback();
        if (isset($feedback[$field])) {
            return $feedback[$field];
        }
        $results = [];
        foreach ($feedback as $registeredName => $errorMessages) {
            if (str_starts_with($registeredName, $field)) {
                $results = array_merge($results, $errorMessages);
            }
        }
        return $results;
    }

    /**
     * Retrieves the field names which contains error messages. This will return the array notation instead of the
     * pathing for easier html manipulation. E.g. cart[].quantity.2 -> cart[quantity][2].
     *
     * @return array
     */
    public static function getFieldNames(): array
    {
        $feedbackKeys = [];
        foreach (self::readAll() as $key => $messages) {
            $feedbackKeys[] = self::pathingToFieldName($key);
        }
        return $feedbackKeys;
    }

    /**
     * Retrieves the whole feedback structure as it is saved in the session.
     *
     * @return array
     */
    public static function readAll(): array
    {
        return self::getSavedFeedback();
    }

    /**
     * Empty all saved feedback messages in the session.
     */
    public static function clear(): void
    {
        Session::getInstance()->remove(self::SESSION_KEY);
    }

    /**
     * Retrieves the feedbacks from the session. Returns an empty array if none exist.
     *
     * @return array
     */
    private static function getSavedFeedback(): array
    {
        $feedback = Session::getInstance()->read(self::SESSION_KEY);
        if (is_null($feedback)) {
            $feedback = [];
        }
        return $feedback;
    }

    /**
     * Transforms the pathing into HTML array notation. E.g. cart[].quantity.2 -> cart[quantity][2].
     *
     * @param string $inputString
     * @return string
     */
    private static function pathingToFieldName(string $inputString): string
    {
        if (!str_contains($inputString, '.')) {
            return $inputString;
        }
        $transformedString = str_replace(['[].', '.'], ['[', ']['], $inputString);
        $transformedString .= ']';
        return $transformedString;
    }
}
