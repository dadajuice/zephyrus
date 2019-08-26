<?php namespace Zephyrus\Utilities;

use Zephyrus\Utilities\Validations\BaseValidation;
use Zephyrus\Utilities\Validations\SpecializedValidations;
use Zephyrus\Utilities\Validations\StringValidations;
use Zephyrus\Utilities\Validations\TimeValidations;

class Validation
{
    use BaseValidation;
    use SpecializedValidations;
    use StringValidations;
    use TimeValidations;
}
