<?php namespace Zephyrus\Utilities\Validations;

interface ValidationCallback
{
    const PASSWORD_COMPLIANT = ['Zephyrus\Utilities\Validation', 'isPasswordCompliant'];
    const NOT_EMPTY = ['Zephyrus\Utilities\Validation', 'isNotEmpty'];
    const DECIMAL = ['Zephyrus\Utilities\Validation', 'isDecimal'];
    const DECIMAL_SIGNED = ['Zephyrus\Utilities\Validation', 'isSignedDecimal'];
    const INTEGER = ['Zephyrus\Utilities\Validation', 'isInteger'];
    const INTEGER_SIGNED = ['Zephyrus\Utilities\Validation', 'isSignedInteger'];
    const EMAIL = ['Zephyrus\Utilities\Validation', 'isEmail'];
    const DATE_ISO = ['Zephyrus\Utilities\Validation', 'isDate'];
    const TIME_12HOURS = ['Zephyrus\Utilities\Validation', 'isTime12Hours'];
    const TIME_24HOURS = ['Zephyrus\Utilities\Validation', 'isTime24Hours'];
    const DATE_TIME_12HOURS = ['Zephyrus\Utilities\Validation', 'isDateTime24Hours'];
    const DATE_TIME_24HOURS = ['Zephyrus\Utilities\Validation', 'isDateTime24Hours'];
    const ALPHA = ['Zephyrus\Utilities\Validation', 'isAlpha'];
    const NAME = ['Zephyrus\Utilities\Validation', 'isName'];
    const ALPHANUMERIC = ['Zephyrus\Utilities\Validation', 'isAlphanumeric'];
    const URL = ['Zephyrus\Utilities\Validation', 'isUrl'];
    const URL_STRICT = ['Zephyrus\Utilities\Validation', 'isStrictUrl'];
    const URL_YOUTUBE = ['Zephyrus\Utilities\Validation', 'isYoutubeUrl'];
    const PHONE = ['Zephyrus\Utilities\Validation', 'isPhone'];
    const ZIP_CODE = ['Zephyrus\Utilities\Validation', 'isZipCode'];
    const POSTAL_CODE = ['Zephyrus\Utilities\Validation', 'isPostalCode'];
    const BOOLEAN = ['Zephyrus\Utilities\Validation', 'isBoolean'];
    const IN_RANGE = ['Zephyrus\Utilities\Validation', 'isInRange'];
    const REGEX = ['Zephyrus\Utilities\Validation', 'isRegex'];
}
