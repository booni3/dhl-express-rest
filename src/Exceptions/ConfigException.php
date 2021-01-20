<?php


namespace Booni3\DhlExpressRest;


class ConfigException extends \Exception
{
    public static function missingArgument($arg)
    {
        throw new static($arg.' is missing');
    }
}
