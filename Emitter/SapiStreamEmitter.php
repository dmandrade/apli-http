<?php
/**
 *  Copyright (c) 2019 Danilo Andrade.
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file SapiStreamEmitter.php
 *
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 03/02/19 at 20:52
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 03/02/2019
 * Time: 20:52.
 */

namespace Apli\Http\Emitter;

use Psr\Http\Message\ResponseInterface;

class SapiStreamEmitter extends AbstractSapiEmitter
{
    /**
     * Maximum output buffering size for each iteration.
     *
     * @var int
     */
    protected $maxBufferLength = 8192;

    /**
     * Set the maximum output buffering level.
     *
     * @param int $maxBufferLength
     *
     * @return EmitterInterface
     */
    public function setMaxBufferLength(int $maxBufferLength)
    {
        $this->maxBufferLength = $maxBufferLength;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function emit(ResponseInterface $response)
    {
        $this->assertNoPreviousOutput();
        $this->emitHeaders($response);
        // Set the status _after_ the headers, because of PHP's "helpful" behavior with location headers.
        $this->emitStatusLine($response);
        $range = $this->parseContentRange($response->getHeaderLine('Content-Range'));
        if (\is_array($range) && $range[0] === 'bytes') {
            $this->emitBodyRange($range, $response, $this->maxBufferLength);
        } else {
            $this->emitBody($response, $this->maxBufferLength);
        }
        $this->closeConnection();
    }

    /**
     * Parse content-range header
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.16.
     *
     * @param string $header
     *
     * @return null|array [unit, first, last, length]; returns false if no
     *                    content range or an invalid content range is provided
     */
    private function parseContentRange($header)
    {
        if (\preg_match('/(?P<unit>[\w]+)\s+(?P<first>\d+)-(?P<last>\d+)\/(?P<length>\d+|\*)/', $header, $matches) === 1) {
            return [
                $matches['unit'],
                (int) $matches['first'],
                (int) $matches['last'],
                $matches['length'] === '*' ? '*' : (int) $matches['length'],
            ];
        }
    }

    /**
     * Emit a range of the message body.
     *
     * @param array             $range
     * @param ResponseInterface $response
     * @param int               $maxBufferLength
     */
    private function emitBodyRange(array $range, ResponseInterface $response, int $maxBufferLength)
    {
        [$unit, $first, $last, $length] = $range;
        $body = $response->getBody();
        $length = $last - $first + 1;
        if ($body->isSeekable()) {
            $body->seek($first);
            $first = 0;
        }
        if (!$body->isReadable()) {
            echo \substr($body->getContents(), $first, (int) $length);

            return;
        }
        $remaining = $length;
        while ($remaining >= $maxBufferLength && !$body->eof()) {
            $contents = $body->read($maxBufferLength);
            $remaining -= \strlen($contents);
            echo $contents;
        }
        if ($remaining > 0 && !$body->eof()) {
            echo $body->read((int) $remaining);
        }
    }

    /**
     * Sends the message body of the response.
     *
     * @param ResponseInterface $response
     * @param int               $maxBufferLength
     */
    private function emitBody(ResponseInterface $response, int $maxBufferLength)
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        if (!$body->isReadable()) {
            echo $body;

            return;
        }
        while (!$body->eof()) {
            echo $body->read($maxBufferLength);
        }
    }
}
