<?php namespace Zephyrus\Security\IntrusionDetection;

use stdClass;
use Zephyrus\Security\IntrusionDetection\Converters\EncodingConverter;
use Zephyrus\Security\IntrusionDetection\Converters\JavascriptConverter;
use Zephyrus\Security\IntrusionDetection\Converters\SqlConverter;
use Zephyrus\Security\IntrusionDetection\Converters\StringConverter;

class IntrusionMonitor
{
    /**
     * List of all the IDS rules to be verifies.
     *
     * @var array
     */
    private array $rules;

    /**
     * List of parameter names to be excluded from the IDS monitoring. If one of these parameters are encountered, the
     * monitor will skill them entirely. Be cautious about the security implications.
     *
     * @var array
     */
    private array $exceptions = [];

    /**
     * Includes the converter traits used to filter classic known obfuscating methods.
     */
    use StringConverter;
    use SqlConverter;
    use JavascriptConverter;
    use EncodingConverter;

    /**
     * Prepares the monitor with a set of IDS rules to verify.
     *
     * @param array $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Specifies the list of parameters to be excluded from the monitoring.
     *
     * @param array $parameters
     */
    public function setExceptions(array $parameters)
    {
        $this->exceptions = $parameters;
    }

    /**
     * Executes the monitoring of the given data. Must be associative array with the key being the parameter name. Will
     * return the resulting impact. The complete details can be obtained with the getReports method.
     *
     * @param array $data
     * @return IntrusionReport
     */
    public function run(array $data): IntrusionReport
    {
        $report = new IntrusionReport();
        foreach ($data as $parameter => $value) {
            if ($this->isExempt((string) $parameter)) {
                continue;
            }
            foreach ($this->rules as $rule) {
                $this->detectIntrusion($rule, $parameter, $value, $report);
            }
        }
        $report->end();
        return $report;
    }

    /**
     * Verifies if the parameter is exempt from IDS mitigation. Meaning it has been defined as an exception either
     * directly or regex.
     *
     * @param string $parameter
     * @return bool
     */
    private function isExempt(string $parameter): bool
    {
        foreach ($this->exceptions as $exception) {
            if ($exception === $parameter) {
                return true;
            }
            if (preg_match('/^' . $exception . '$/', $parameter)) {
                return true;
            }
        }
        return false;
    }

    private function detectIntrusion(stdClass $rule, $parameter, $data, IntrusionReport $report)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $this->detectIntrusion($rule, $parameter . '.' . $key, $value, $report);
            }
        } elseif (is_string($data)) {
            if ($this->isSimplyAlphanumeric($data)) {
                return;
            }
            $data = $this->convert($data);
            if (preg_match('/'. $rule->rule .'/im', $data) === 1) {
                $report->addIntrusion($rule, $parameter, $data);
            }
        }
    }

    /**
     * Only start detection if value isn't alphanumeric.
     *
     * @param string $data
     * @return bool
     */
    private function isSimplyAlphanumeric(string $data): bool
    {
        // Only start detection if value isn't alphanumeric
        $preFilter = '([^\w\s/@!?\.]+|(?:\./)|(?:@@\w+)|(?:\+ADw)|(?:union\s+select))i';
        if (!$data || !preg_match($preFilter, $data)) {
            return true;
        }
        return false;
    }

    /**
     * Cleans possible obfuscating used on the value. Concepts and code kindly obtained from the PHPIDS project with
     * permission from original author.
     *
     * @see https://github.com/PHPIDS/PHPIDS
     * @param string $value
     * @return string
     */
    private function convert(string $value): string
    {
        // Encoding based conversions
        $value = $this->convertFromNestedBase64($value);
        $value = $this->convertFromUTF7($value);
        $value = $this->convertFromProprietaryEncodings($value);

        // String based conversions
        $value = $this->convertEntities($value);
        $value = $this->convertFromCommented($value);
        $value = $this->convertFromWhiteSpace($value);
        $value = $this->convertQuotes($value);
        $value = $this->convertFromControlChars($value);
        $value = $this->convertFromOutOfRangeChars($value);
        $value = $this->convertFromXML($value);
        $value = $this->convertFromConcatenated($value);

        // SQL based conversions
        $value = $this->convertFromSQLKeywords($value);
        $value = $this->convertFromSQLHex($value);
        $value = $this->convertFromUrlencodedSqlComment($value);

        // Javascript based conversions
        $value = $this->convertFromJSCharCode($value);
        $value = $this->convertJSRegexModifiers($value);
        return $this->convertFromJSUnicode($value);
    }
}
