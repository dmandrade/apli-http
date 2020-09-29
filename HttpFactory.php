<?php
/**
 *  Copyright (c) 2019 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file Factory.php
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 17/11/19 at 16:51
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 17/11/2019
 * Time: 16:51
 */

namespace Apli\Http;

use Apli\Http\Response\DefaultResponse;
use Apli\Http\Stream\DefaultStream;
use Apli\Uri\Url;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface,
    ServerRequestInterface,
    StreamInterface,
    UploadedFileInterface,
    UriInterface
};


class HttpFactory implements HttpFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new DefaultServerRequest($method, $uri);
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new DefaultResponse($code, [], null, $reasonPhrase);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return new DefaultStream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = @\fopen($filename, $mode);
        if (false === $resource) {
            if ('' === $mode || false === \in_array($mode[0], ['r', 'w', 'a', 'x', 'c'])) {
                throw new \InvalidArgumentException('The mode '.$mode.' is invalid.');
            }
            throw new \RuntimeException('The file '.$filename.' cannot be opened.');
        }
        return new DefaultStream($resource);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return new DefaultStream($resource);
    }

    public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = \UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null): UploadedFileInterface
    {
        if (null === $size) {
            $size = $stream->getSize();
        }
        return new DefaultUploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return Url::createFromString($uri);
    }

    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new DefaultServerRequest($method, $uri, [], null, static::marshalProtocolVersionFromSapi($serverParams), $serverParams);
    }

    /**
     * Return HTTP protocol version (X.Y) as discovered within a `$_SERVER` array.
     *
     * @param array $serverParams
     *
     * @throws UnexpectedValueException if the $server['SERVER_PROTOCOL'] value is
     *                                  malformed.
     *
     * @return string
     */
    protected static function marshalProtocolVersionFromSapi(array $serverParams)
    {
        if (!isset($serverParams['SERVER_PROTOCOL'])) {
            return '1.1';
        }

        if (!preg_match('#^(HTTP/)?(?P<version>[1-9]\d*(?:\.\d)?)$#', $serverParams['SERVER_PROTOCOL'], $matches)) {
            throw new UnexpectedValueException(sprintf(
                'Unrecognized protocol version (%s)',
                $serverParams['SERVER_PROTOCOL']
            ));
        }

        return $matches['version'];
    }
}
