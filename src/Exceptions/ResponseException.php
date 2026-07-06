<?php


namespace Booni3\DhlExpressRest\Exceptions;


class ResponseException extends \Exception
{
    public static function parseError(string $response)
    {
        throw new static('Could not parse response: '.$response);
    }

    public static function clientException(array $response, ?int $statusCode = null)
    {
        throw new static(json_encode($response), (int) ($response['status'] ?? $statusCode ?? 0));
    }
}
