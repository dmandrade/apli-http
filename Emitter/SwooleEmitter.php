<?php
/**
 *  Copyright (c) 2019 Danilo Andrade.
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file SwooleEmitter.php
 *
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 03/02/19 at 20:53
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 03/02/2019
 * Time: 20:53.
 */

namespace Apli\Http\Emitter;

use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response as SwooleHttpResponse;
use function extension_loaded;

class SwooleEmitter extends AbstractSapiEmitter
{
    /**
     * @see https://www.swoole.co.uk/docs/modules/swoole-http-server/methods-properties#swoole-http-response-write
     */
    private $chunkSize;
    /**
     * @var SwooleHttpResponse
     */
    private $swooleResponse;

    /**
     * SwooleEmitter constructor.
     * @param SwooleHttpResponse $swooleResponse
     * @param int $chunkSize
     */
    public function __construct(SwooleHttpResponse $swooleResponse, int $chunkSize = 2097152)
    {
        $this->swooleResponse = $swooleResponse;
        $this->chunkSize      = $chunkSize;
    }

    /**
     * @return int
     */
    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }


    /**
     * {@inheritdoc}
     */
    public function emit(ResponseInterface $response): void
    {
        if (PHP_SAPI !== 'cli' || !extension_loaded('swoole')) {
            return;
        }

        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        $this->emitBody($response);
    }

    /**
     * {@inheritdoc}
     */
    protected function emitStatusLine(ResponseInterface $response): void
    {
        $this->swooleResponse->status($response->getStatusCode());
    }

    /**
     * {@inheritdoc}
     */
    protected function emitHeaders(ResponseInterface $response): void
    {
        $this->swooleResponse->status($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            $name = $this->toWordCase($name);
            $this->swooleResponse->header($name, implode(', ', $values));
        }
    }
    /**
     * Emit the message body.
     *
     * @return void
     */
    private function emitBody(ResponseInterface $response)
    {
        $body = $response->getBody();
        $body->rewind();
        if ($body->getSize() <= $this->chunkSize) {
            $this->swooleResponse->end($body->getContents());
            return;
        }
        while (! $body->eof()) {
            $this->swooleResponse->write($body->read($this->chunkSize));
        }
        $this->swooleResponse->end();
    }
}
