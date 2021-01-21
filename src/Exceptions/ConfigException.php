<?php


namespace Booni3\DhlExpressRest\Exceptions;


class ConfigException extends \Exception
{
    public static function missingArgument($arg)
    {
        throw new static($arg.' is missing');
    }
}
