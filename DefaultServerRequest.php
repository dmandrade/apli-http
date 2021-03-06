<?php
/**
 *  Copyright (c) 2018 Danilo Andrade.
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file ServerRequest.php
 *
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 30/06/18 at 13:11
 */

namespace Apli\Http;

use Apli\Http\Stream\PhpInputStream;
use Apli\Http\Traits\MessageTrait;
use Apli\Http\Traits\RequestTrait;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Server-side HTTP request.
 *
 * Extends the Request definition to add methods for accessing incoming data,
 * specifically server parameters, cookies, matched path parameters, query
 * string arguments, body parameters, and upload file information.
 *
 * "Attributes" are discovered via decomposing the request (and usually
 * specifically the URI path), and typically will be injected by the application.
 *
 * Requests are considered immutable; all methods that might change state are
 * implemented such that they retain the internal state of the current
 * message and return a new instance that contains the changed state.
 */
class DefaultServerRequest implements ServerRequestInterface
{
    use MessageTrait;
    use RequestTrait;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $cookieParams = [];

    /**
     * @var null|array|object
     */
    protected $parsedBody;

    /**
     * @var array
     */
    protected $queryParams = [];

    /**
     * @var array
     */
    protected $serverParams;

    /**
     * @var array
     */
    protected $uploadedFiles;

    /**
     * @param null|string                     $method        HTTP method for the request, if any.
     * @param null|string|UriInterface        $uri           URI for the request, if any.
     * @param array                           $headers       Headers for the message, if any.
     * @param StreamInterface|string|resource $body          Message body, if any.
     * @param string                          $protocol      HTTP protocol version.
     * @param array                           $serverParams  Server parameters, typically from $_SERVER
     * @param array                           $uploadedFiles Upload file information, a tree of UploadedFiles
     * @param array                           $cookies       Cookies for the message, if any.
     * @param array                           $queryParams   Query params for the message, if any.
     * @param null|array|object               $parsedBody    The deserialized body parameters, if any.
     *
     * @throws InvalidArgumentException for any invalid value.
     */
    public function __construct(
        $method = null,
        $uri = null,
        array $headers = [],
        $body = 'php://input',
        $protocol = '1.1',
        array $serverParams = [],
        array $uploadedFiles = [],
        array $cookies = [],
        array $queryParams = [],
        $parsedBody = null
    ) {
        $this->validateUploadedFiles($uploadedFiles);

        if (null === $body || $body === 'php://input') {
            $body = new PhpInputStream();
        }

        $this->initialize($uri, $method, $body, $headers);
        $this->serverParams = $serverParams;
        $this->uploadedFiles = $uploadedFiles;
        $this->cookieParams = $cookies;
        $this->queryParams = $queryParams;
        $this->parsedBody = $parsedBody;
        $this->protocol = $protocol;
    }

    /**
     * Recursively validate the structure in an uploaded files array.
     *
     * @param array $uploadedFiles
     *
     * @throws InvalidArgumentException if any leaf is not an UploadedFileInterface instance.
     */
    private function validateUploadedFiles(array $uploadedFiles)
    {
        foreach ($uploadedFiles as $file) {
            if (is_array($file)) {
                $this->validateUploadedFiles($file);
                continue;
            }

            if (!$file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException('Invalid leaf in uploaded files structure');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $this->validateUploadedFiles($uploadedFiles);
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($attribute, $default = null)
    {
        if (!array_key_exists($attribute, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($attribute, $value)
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($attribute)
    {
        $new = clone $this;
        unset($new->attributes[$attribute]);

        return $new;
    }

    /**
     * Proxy to receive the request method.
     *
     * This overrides the parent functionality to ensure the method is never
     * empty; if no method is present, it returns 'GET'.
     *
     * @return string
     */
    public function getMethod()
    {
        if (empty($this->method)) {
            return 'GET';
        }

        return $this->method;
    }

    /**
     * Set the request method.
     *
     * Unlike the regular Request implementation, the server-side
     * normalizes the method to uppercase to ensure consistency
     * and make checking the method simpler.
     *
     * This methods returns a new instance.
     *
     * @param string $method
     *
     * @return self
     */
    public function withMethod($method)
    {
        $this->validateMethod($method);
        $new = clone $this;
        $new->method = $method;

        return $new;
    }
}
