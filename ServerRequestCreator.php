<?php
/**
 *  Copyright (c) 2019 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file ServerRequestCreator.php
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 17/11/19 at 16:40
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 17/11/2019
 * Time: 16:40
 */

namespace Apli\Http;

use Apli\Http\Stream\SwooleStream;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Swoole\Http\Request as SwooleHttpRequest;

/**
 * Class ServerRequestCreator
 * @package Apli\Http
 */
final class ServerRequestCreator implements ServerRequestCreatorInterface
{
    /**
     * @var ServerRequestFactoryInterface
     */
    private $serverRequestFactory;
    /**
     * @var UriFactoryInterface
     */
    private $uriFactory;
    /**
     * @var UploadedFileFactoryInterface
     */
    private $uploadedFileFactory;
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * ServerRequestCreator constructor.
     * @param ServerRequestFactoryInterface $serverRequestFactory
     * @param UriFactoryInterface           $uriFactory
     * @param UploadedFileFactoryInterface  $uploadedFileFactory
     * @param StreamFactoryInterface        $streamFactory
     */
    public function __construct(
        ServerRequestFactoryInterface $serverRequestFactory,
        UriFactoryInterface $uriFactory,
        UploadedFileFactoryInterface $uploadedFileFactory,
        StreamFactoryInterface $streamFactory
    )
    {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->uriFactory = $uriFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function fromGlobals(): ServerRequestInterface
    {
        $server = $_SERVER;
        if (false === isset($server['REQUEST_METHOD'])) {
            $server['REQUEST_METHOD'] = 'GET';
        }

        return $this->fromArrays(
            $server,
            static::getHeadersFromServer($_SERVER),
            $_COOKIE,
            $_GET,
            $_POST,
            $_FILES,
            \fopen('php://input', 'r') ?: null
        );
    }

    public function fromSwoole(SwooleHttpRequest $request): ServerRequestInterface
    {
        // Aggregate values from Swoole request object
        $get     = $request->get ?? [];
        $post    = $request->post ?? [];
        $cookie  = $request->cookie ?? [];
        $files   = $request->files ?? [];
        $server  = $request->server ?? [];
        $headers = $request->header ?? [];
        // Normalize SAPI params
        $server = array_change_key_case($server, CASE_UPPER);


        return $this->fromArrays(
            $server,
            $headers,
            $cookie,
            $get,
            $post,
            $files,
            new SwooleStream($request)
        );
    }

    /**
     * @param array $server
     * @param array $headers
     * @param array $cookie
     * @param array $get
     * @param array $post
     * @param array $files
     * @param null  $body
     * @return ServerRequestInterface
     */
    public function fromArrays(array $server, array $headers = [], array $cookie = [], array $get = [], array $post = [], array $files = [], $body = null): ServerRequestInterface
    {
        $method = $this->getMethodFromEnv($server);
        $uri = $this->getUriFromEnvWithHTTP($server);
        $protocol = isset($server['SERVER_PROTOCOL']) ? \str_replace('HTTP/', '', $server['SERVER_PROTOCOL']) : '1.1';
        $serverRequest = $this->serverRequestFactory->createServerRequest($method, $uri, $server);
        foreach ($headers as $name => $value) {
            $serverRequest = $serverRequest->withAddedHeader($name, $value);
        }
        $serverRequest = $serverRequest
            ->withProtocolVersion($protocol)
            ->withCookieParams($cookie)
            ->withQueryParams($get)
            ->withParsedBody($post)
            ->withUploadedFiles($this->normalizeFiles($files));
        if (null === $body) {
            return $serverRequest;
        }
        if (\is_resource($body)) {
            $body = $this->streamFactory->createStreamFromResource($body);
        } elseif (\is_string($body)) {
            $body = $this->streamFactory->createStream($body);
        } elseif (!$body instanceof StreamInterface) {
            throw new \InvalidArgumentException('The $body parameter to ServerRequestCreator::fromArrays must be string, resource or StreamInterface');
        }
        return $serverRequest->withBody($body);
    }

    /**
     * Implementation from Zend\Diactoros\marshalHeadersFromSapi().
     *
     * @param array $server
     * @return array
     */
    public static function getHeadersFromServer(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            // Apache prefixes environment variables with REDIRECT_
            // if they are added by rewrite rules
            if (0 === \strpos($key, 'REDIRECT_')) {
                $key = \substr($key, 9);
                // We will not overwrite existing variables with the
                // prefixed versions, though
                if (\array_key_exists($key, $server)) {
                    continue;
                }
            }
            if ($value && 0 === \strpos($key, 'HTTP_')) {
                $name = \strtr(\strtolower(\substr($key, 5)), '_', '-');
                $headers[$name] = $value;
                continue;
            }
            if ($value && 0 === \strpos($key, 'CONTENT_')) {
                $name = 'content-'.\strtolower(\substr($key, 8));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    /**
     * @param array $environment
     * @return string
     */
    private function getMethodFromEnv(array $environment): string
    {
        if (false === isset($environment['REQUEST_METHOD'])) {
            throw new \InvalidArgumentException('Cannot determine HTTP method');
        }
        return $environment['REQUEST_METHOD'];
    }

    /**
     * @param array $environment
     * @return UriInterface
     */
    private function getUriFromEnvWithHTTP(array $environment): UriInterface
    {
        $uri = $this->createUriFromArray($environment);
        if (empty($uri->getScheme())) {
            $uri = $uri->withScheme('http');
        }
        return $uri;
    }

    /**
     * Return an UploadedFile instance array.
     *
     * @param array $files
     * @return array
     */
    private function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
                continue;
            }

            if (\is_array($value)) {
                if (isset($value['tmp_name'])) {
                    $normalized[$key] = $this->createUploadedFileFromSpec($value);
                    continue;
                }

                $normalized[$key] = $this->normalizeFiles($value);
                continue;
            }

            throw new \InvalidArgumentException('Invalid value in files specification');
        }
        return $normalized;
    }

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * delegate to normalizeNestedFileSpec() and return that return value
     *
     * @param array $value
     * @return array|UploadedFileInterface
     */
    private function createUploadedFileFromSpec(array $value)
    {
        if (\is_array($value['tmp_name'])) {
            return $this->normalizeNestedFileSpec($value);
        }
        try {
            $stream = $this->streamFactory->createStreamFromFile($value['tmp_name']);
        } catch (\RuntimeException $e) {
            $stream = $this->streamFactory->createStream();
        }
        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            (int)$value['size'],
            (int)$value['error'],
            $value['name'],
            $value['type']
        );
    }

    /**
     * Normalize an array of file specifications.
     *
     * Loops through all nested files and returns a normalized array of
     * UploadedFileInterface instances.
     *
     * @param array $files
     * @return array
     */
    private function normalizeNestedFileSpec(array $files = []): array
    {
        $normalizedFiles = [];
        foreach (\array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
            ];
            $normalizedFiles[$key] = $this->createUploadedFileFromSpec($spec);
        }
        return $normalizedFiles;
    }

    /**
     * Create a new uri from server variable.
     *
     * @param array $server
     * @return UriInterface
     */
    private function createUriFromArray(array $server): UriInterface
    {
        $uri = $this->uriFactory->createUri('');
        if (isset($server['HTTP_X_FORWARDED_PROTO'])) {
            $uri = $uri->withScheme($server['HTTP_X_FORWARDED_PROTO']);
        } else {
            if (isset($server['REQUEST_SCHEME'])) {
                $uri = $uri->withScheme($server['REQUEST_SCHEME']);
            } elseif (isset($server['HTTPS'])) {
                $uri = $uri->withScheme('on' === $server['HTTPS'] ? 'https' : 'http');
            }
            if (isset($server['SERVER_PORT'])) {
                $uri = $uri->withPort($server['SERVER_PORT']);
            }
        }
        if (isset($server['HTTP_HOST'])) {
            if (1 === \preg_match('/^(.+)\:(\d+)$/', $server['HTTP_HOST'], $matches)) {
                $uri = $uri->withHost($matches[1])->withPort($matches[2]);
            } else {
                $uri = $uri->withHost($server['HTTP_HOST']);
            }
        } elseif (isset($server['SERVER_NAME'])) {
            $uri = $uri->withHost($server['SERVER_NAME']);
        }
        if (isset($server['REQUEST_URI'])) {
            $uri = $uri->withPath(\current(\explode('?', $server['REQUEST_URI'])));
        }
        if (isset($server['QUERY_STRING'])) {
            $uri = $uri->withQuery($server['QUERY_STRING']);
        }
        return $uri;
    }
}
