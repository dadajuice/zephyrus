<?php namespace Zephyrus\Utilities;

use Zephyrus\Utilities\Validations\BaseValidations;
use Zephyrus\Utilities\Validations\FileValidations;
use Zephyrus\Utilities\Validations\SpecializedValidations;
use Zephyrus\Utilities\Validations\StringValidations;
use Zephyrus\Utilities\Validations\TimeValidations;

class Validation
{
    use BaseValidations;
    use SpecializedValidations;
    use StringValidations;
    use TimeValidations;
    use FileValidations;
}
