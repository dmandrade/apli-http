<?php
/**
 *  Copyright (c) 2019 Danilo Andrade.
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file Util.php
 *
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 03/02/19 at 20:55
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 03/02/2019
 * Time: 20:55.
 */

namespace Apli\Http\Emitter;

use Psr\Http\Message\ResponseInterface;

final class Util
{
    /**
     * Private constructor; non-instantiable.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Inject the Content-Length header if is not already present.
     *
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public static function injectContentLength(ResponseInterface $response)
    {
        // PSR-7 indicates int OR null for the stream size; for null values,
        // we will not auto-inject the Content-Length.
        if (!$response->hasHeader('Content-Length') &&
            $response->getBody()->getSize() !== null
        ) {
            $response = $response->withHeader('Content-Length', (string) $response->getBody()->getSize());
        }

        return $response;
    }

    /**
     * Cleans or flushes output buffers up to target level.
     *
     * Resulting level can be greater than target level if a non-removable buffer has been encountered.
     *
     * @param int  $maxBufferLevel The target output buffering level
     * @param bool $flush          Whether to flush or clean the buffers
     *
     * @return void
     */
    public static function closeOutputBuffers(int $maxBufferLevel, bool $flush)
    {
        $status = \ob_get_status(true);
        $level = \count($status);
        $flags = \PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? \PHP_OUTPUT_HANDLER_FLUSHABLE : \PHP_OUTPUT_HANDLER_CLEANABLE);
        while ($level-- > $maxBufferLevel && (bool) ($s = $status[$level]) && ($s['del'] ?? !isset($s['flags']) || $flags === ($s['flags'] & $flags))) {
            if ($flush) {
                \ob_end_flush();
            } else {
                \ob_end_clean();
            }
        }
    }
}
