<?php

namespace WebmanTech\DTO\Exceptions;

use Throwable;
use WebmanTech\DTO\Helper\ConfigHelper;

final class DTOAssignPropertyException extends DTOValidateFailsException
{
    public function __construct(string $property, Throwable $e)
    {
        $formatter = ConfigHelper::get('dto.type_assign_property_exception_message', fn(string $property) => "assign property error: {$property}");
        $message = $formatter($property, $e);

        parent::__construct([$property => $message], $e);
    }
}
