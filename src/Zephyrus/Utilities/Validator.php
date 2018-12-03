<?php namespace Zephyrus\Utilities;

interface Validator
{
    const PASSWORD_COMPLIANT = ['Zephyrus\Utilities\Validations\BaseValidation', 'isPasswordCompliant'];
    const NOT_EMPTY = ['Zephyrus\Utilities\Validations\BaseValidation', 'isNotEmpty'];
    const DECIMAL = ['Zephyrus\Utilities\Validations\NumericValidation', 'isDecimal'];
    const DECIMAL_SIGNED = ['Zephyrus\Utilities\Validations\NumericValidation', 'isSignedDecimal'];
    const INTEGER = ['Zephyrus\Utilities\Validations\NumericValidation', 'isInteger'];
    const INTEGER_SIGNED = ['Zephyrus\Utilities\Validations\NumericValidation', 'isSignedInteger'];
    const EMAIL = ['Zephyrus\Utilities\Validations\BaseValidation', 'isEmail'];
    const DATE_ISO = ['Zephyrus\Utilities\Validations\BaseValidation', 'isDate'];
    const TIME_12HOURS = ['Zephyrus\Utilities\Validations\BaseValidation', 'isTime12Hours'];
    const TIME_24HOURS = ['Zephyrus\Utilities\Validations\BaseValidation', 'isTime24Hours'];
    const ALPHA = ['Zephyrus\Utilities\Validations\BaseValidation', 'isAlpha'];
    const NAME = ['Zephyrus\Utilities\Validations\BaseValidation', 'isName'];
    const ALPHANUMERIC = ['Zephyrus\Utilities\Validations\BaseValidation', 'isAlphanumeric'];
    const URL = ['Zephyrus\Utilities\Validations\BaseValidation', 'isUrl'];
    const URL_STRICT = ['Zephyrus\Utilities\Validations\BaseValidation', 'isStrictUrl'];
    const URL_YOUTUBE = ['Zephyrus\Utilities\Validations\BaseValidation', 'isYoutubeUrl'];
    const PHONE = ['Zephyrus\Utilities\Validations\BaseValidation', 'isPhone'];
    const ZIPCODE = ['Zephyrus\Utilities\Validations\BaseValidation', 'isZipCode'];
}
