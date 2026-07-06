<?php


namespace Booni3\DhlExpressRest;


class AddressException extends \Exception
{
    public static function validationException(string $field)
    {
        throw new static("Validation Exception For Address: $field");
    }
}
