<?php
/**
 *  Copyright (c) 2019 Danilo Andrade.
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file AbstractSapiEmitter.php
 *
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 03/02/19 at 20:46
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 03/02/2019
 * Time: 20:46.
 */

namespace Apli\Http\Emitter;

use Apli\Http\Exception\EmitterException;
use Psr\Http\Message\ResponseInterface;
use function fastcgi_finish_request;
use function function_exists;
use function header;
use function in_array;
use function sprintf;
use function str_replace;
use function ucwords;
use function vsprintf;
use function rtrim;

abstract class AbstractSapiEmitter implements EmitterInterface
{

    /**
     * Assert either that no headers been sent or the output buffer contains no content.
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    protected function assertNoPreviousOutput(): void
    {
        if (headers_sent($file, $line)) {
            throw EmitterException::forHeadersSent();
        }
        if (\ob_get_level() > 0 && \ob_get_length() > 0) {
            throw EmitterException::forOutputSent();
        }
    }

    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is availble, it, too, is emitted.
     *
     * This method should be called after `emitBody()` to prevent PHP from
     * changing the status code of the emitted response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return void
     */
    protected function emitStatusLine(ResponseInterface $response): void
    {
        $statusCode   = $response->getStatusCode();
        header(
            vsprintf(
                'HTTP/%s %d%s',
                [
                    $response->getProtocolVersion(),
                    $statusCode,
                    rtrim(' ' . $response->getReasonPhrase()),
                ]
            ), true, $statusCode);
    }

    /**
     * Emit response headers.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return void
     */
    protected function emitHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        foreach ($response->getHeaders() as $header => $values) {
            $name  = $this->toWordCase($header);
            $first = $name !== 'Set-Cookie';
            foreach ($values as $value) {
                header(
                    sprintf(
                        '%s: %s',
                        $name,
                        $value
                    ),
                    $first,
                    $statusCode
                );

                $first = false;
            }
        }
    }
    /**
     * Filter a header name to wordcase
     */
    protected function toWordCase(string $header) : string
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);

        return str_replace(' ', '-', $filtered);
    }

    /**
     * Flushes output buffers and closes the connection to the client,
     * which ensures that no further output can be sent.
     *
     * @return void
     */
    protected function closeConnection(): void
    {
        if (!in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            Util::closeOutputBuffers(0, true);
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
}
