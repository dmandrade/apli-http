<?php
/**
 *  Copyright (c) 2018 Danilo Andrade.
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file DefaultResponse.php
 *
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 04/09/18 at 11:55
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 04/09/2018
 * Time: 11:55.
 */

namespace Apli\Http\Response;

use Apli\Http\Stream\FileAccess;
use Apli\Http\Traits\MessageTrait;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use function is_int;
use function gettype;
use function is_scalar;

class DefaultResponse implements ResponseInterface
{
    use MessageTrait;

    public const MIN_STATUS_CODE_VALUE = 100;
    public const MAX_STATUS_CODE_VALUE = 599;

    /**
     * Map of standard HTTP status code/reason phrases.
     *
     * @var array
     */
    public const PHRASES =  [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated to 306 => '(Unused)'
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        // SERVER ERROR
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    /**
     * @var string
     */
    private $reasonPhrase;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * DefaultResponse constructor.
     * @param int    $status
     * @param array  $headers
     * @param null   $body
     * @param mixed $reasonPhrase
     */
    public function __construct(int $status = 200, array $headers = [], $body = 'php://memory', $reasonPhrase = null)
    {
        $this->stream = $this->getStream($body, FileAccess::BINARY_READ_WRITE_FROM_BEGIN());
        $this->setStatusCode($status, $reasonPhrase);
        $this->setHeaders($headers);
    }

    /**
     * Set a valid status code.
     *
     * @param int    $code
     * @param string $reasonPhrase
     *
     * @throws InvalidArgumentException on an invalid status code.
     */
    private function setStatusCode($code, $reasonPhrase = null): void
    {
        if (!is_int($code)
            || $code < static::MIN_STATUS_CODE_VALUE
            || $code > static::MAX_STATUS_CODE_VALUE
        ) {
            throw new InvalidArgumentException(sprintf(
                'Invalid status code "%s"; must be an integer between %d and %d, inclusive',
                is_scalar($code) ? $code : gettype($code),
                static::MIN_STATUS_CODE_VALUE,
                static::MAX_STATUS_CODE_VALUE
            ));
        }

        if ((null === $reasonPhrase || '' === $reasonPhrase) && isset(static::PHRASES[$code])) {
            $reasonPhrase = self::PHRASES[$code];
        }

        $this->reasonPhrase = $reasonPhrase;
        $this->statusCode = (int)$code;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $new = clone $this;
        $new->setStatusCode($code, $reasonPhrase);

        return $new;
    }
}
