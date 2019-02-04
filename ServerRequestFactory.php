<?php
/**
 *  Copyright (c) 2018 Danilo Andrade.
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file ServerRequestFactory.php
 *
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 03/09/18 at 18:11
 */

namespace Apli\Http;

use Apli\Uri\Url;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class for marshaling a request object from the current PHP environment.
 */
abstract class ServerRequestFactory
{
    /**
     * Function to use to get apache request headers; present only to simplify mocking.
     *
     * @var callable
     */
    private static $apacheRequestHeaders = 'apache_request_headers';

    /**
     * Creates a new request with values from PHP's super globals.
     *
     * @throws \Apli\Uri\UriException
     *
     * @return ServerRequestInterface
     */
    public static function createFromGlobals()
    {
        return self::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
    }

    /**
     * Create a request from the supplied superglobal values.
     *
     * If any argument is not supplied, the corresponding superglobal value will
     * be used.
     *
     * The ServerRequest created is then passed to the fromServer() method in
     * order to marshal the request URI and headers.
     *
     * @param array|null $server
     * @param array|null $query
     * @param array|null $body
     * @param array|null $cookies
     * @param array|null $files
     *
     * @throws \Apli\Uri\UriException
     *
     * @return ServerRequestInterface
     */
    public static function fromGlobals(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ) {
        $server = self::normalizeServer(
            $server ?: $_SERVER,
            is_callable(self::$apacheRequestHeaders) ? self::$apacheRequestHeaders : null
        );
        $files = self::normalizeUploadedFiles($files ?: $_FILES);
        $headers = self::marshalHeadersFromSapi($server);

        if (null === $cookies && array_key_exists('cookie', $headers)) {
            $cookies = self::parseCookieHeader($headers['cookie']);
        }

        return new DefaultServerRequest(
            $server,
            $files,
            self::marshalUriFromSapi($server, $headers),
            self::marshalMethodFromSapi($server),
            'php://input',
            $headers,
            $cookies ?: $_COOKIE,
            $query ?: $_GET,
            $body ?: $_POST,
            self::marshalProtocolVersionFromSapi($server)
        );
    }

    /**
     * Marshal the $_SERVER array.
     *
     * Pre-processes and returns the $_SERVER superglobal. In particularly, it
     * attempts to detect the Authorization header, which is often not aggregated
     * correctly under various SAPI/httpd combinations.
     *
     * @param array         $server
     * @param null|callable $apacheRequestHeaderCallback Callback that can be used to
     *                                                   retrieve Apache request headers. This defaults to
     *                                                   `apache_request_headers` under the Apache mod_php.
     *
     * @return array Either $server verbatim, or with an added HTTP_AUTHORIZATION header.
     */
    public static function normalizeServer(array $server, callable $apacheRequestHeaderCallback = null)
    {
        if (null === $apacheRequestHeaderCallback && is_callable('apache_request_headers')) {
            $apacheRequestHeaderCallback = 'apache_request_headers';
        }

        // If the HTTP_AUTHORIZATION value is already set, or the callback is not
        // callable, we return verbatim
        if (isset($server['HTTP_AUTHORIZATION'])
            || !is_callable($apacheRequestHeaderCallback)
        ) {
            return $server;
        }

        $apacheRequestHeaders = $apacheRequestHeaderCallback();
        if (isset($apacheRequestHeaders['Authorization'])) {
            $server['HTTP_AUTHORIZATION'] = $apacheRequestHeaders['Authorization'];

            return $server;
        }

        if (isset($apacheRequestHeaders['authorization'])) {
            $server['HTTP_AUTHORIZATION'] = $apacheRequestHeaders['authorization'];

            return $server;
        }

        return $server;
    }

    /**
     * Normalize uploaded files.
     *
     * Transforms each value into an UploadedFile instance, and ensures that nested
     * arrays are normalized.
     *
     * @param array $files
     *
     * @throws InvalidArgumentException for unrecognized values
     *
     * @return UploadedFileInterface[]
     */
    public static function normalizeUploadedFiles(array $files)
    {
        /**
         * Traverse a nested tree of uploaded file specifications.
         *
         * @param string[]|array[]      $tmpNameTree
         * @param int[]|array[]         $sizeTree
         * @param int[]|array[]         $errorTree
         * @param string[]|array[]|null $nameTree
         * @param string[]|array[]|null $typeTree
         *
         * @return UploadedFileInterface[]|array[]
         */
        $recursiveNormalize = function (
            array $tmpNameTree,
            array $sizeTree,
            array $errorTree,
            array $nameTree = null,
            array $typeTree = null
        ) use (&$recursiveNormalize) {
            $normalized = [];
            foreach ($tmpNameTree as $key => $value) {
                if (is_array($value)) {
                    // Traverse
                    $normalized[$key] = $recursiveNormalize(
                        $tmpNameTree[$key],
                        $sizeTree[$key],
                        $errorTree[$key],
                        isset($nameTree[$key]) ? $nameTree[$key] : null,
                        isset($typeTree[$key]) ? $typeTree[$key] : null
                    );
                    continue;
                }
                $normalized[$key] = self::createUploadedFile([
                    'tmp_name' => $tmpNameTree[$key],
                    'size'     => $sizeTree[$key],
                    'error'    => $errorTree[$key],
                    'name'     => isset($nameTree[$key]) ? $nameTree[$key] : null,
                    'type'     => isset($typeTree[$key]) ? $typeTree[$key] : null,
                ]);
            }

            return $normalized;
        };

        /**
         * Normalize an array of file specifications.
         *
         * Loops through all nested files (as determined by receiving an array to the
         * `tmp_name` key of a `$_FILES` specification) and returns a normalized array
         * of UploadedFile instances.
         *
         * This function normalizes a `$_FILES` array representing a nested set of
         * uploaded files as produced by the php-fpm SAPI, CGI SAPI, or mod_php
         * SAPI.
         *
         * @param array $files
         *
         * @return UploadedFileInterface[]
         */
        $normalizeUploadedFileSpecification = function (array $files = []) use (&$recursiveNormalize) {
            if (!isset($files['tmp_name']) || !is_array($files['tmp_name'])
                || !isset($files['size']) || !is_array($files['size'])
                || !isset($files['error']) || !is_array($files['error'])
            ) {
                throw new InvalidArgumentException(sprintf(
                    '$files provided to %s MUST contain each of the keys "tmp_name",'
                    .' "size", and "error", with each represented as an array;'
                    .' one or more were missing or non-array values',
                    __FUNCTION__
                ));
            }

            return $recursiveNormalize(
                $files['tmp_name'],
                $files['size'],
                $files['error'],
                isset($files['name']) ? $files['name'] : null,
                isset($files['type']) ? $files['type'] : null
            );
        };

        $normalized = [];
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
                continue;
            }

            if (is_array($value) && isset($value['tmp_name']) && is_array($value['tmp_name'])) {
                $normalized[$key] = $normalizeUploadedFileSpecification($value);
                continue;
            }

            if (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::createUploadedFile($value);
                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = self::normalizeUploadedFiles($value);
                continue;
            }

            throw new InvalidArgumentException('Invalid value in files specification');
        }

        return $normalized;
    }

    /**
     * Create an uploaded file instance from an array of values.
     *
     * @param array $spec A single $_FILES entry.
     *
     * @throws InvalidArgumentException if one or more of the tmp_name, size,
     *                                  or error keys are missing from $spec.
     *
     * @return UploadedFileInterface
     */
    public static function createUploadedFile(array $spec)
    {
        if (!isset($spec['tmp_name'])
            || !isset($spec['size'])
            || !isset($spec['error'])
        ) {
            throw new InvalidArgumentException(sprintf(
                '$spec provided to %s MUST contain each of the keys "tmp_name",'
                .' "size", and "error"; one or more were missing',
                __FUNCTION__
            ));
        }

        return new DefaultUploadedFile(
            $spec['tmp_name'],
            $spec['size'],
            $spec['error'],
            isset($spec['name']) ? $spec['name'] : null,
            isset($spec['type']) ? $spec['type'] : null
        );
    }

    /**
     * @param array $server Values obtained from the SAPI (generally `$_SERVER`).
     *
     * @return array Header/value pairs
     */
    public static function marshalHeadersFromSapi(array $server)
    {
        $headers = [];
        foreach ($server as $key => $value) {
            // Apache prefixes environment variables with REDIRECT_
            // if they are added by rewrite rules
            if (strpos($key, 'REDIRECT_') === 0) {
                $key = substr($key, 9);

                // We will not overwrite existing variables with the
                // prefixed versions, though
                if (array_key_exists($key, $server)) {
                    continue;
                }
            }

            if ($value && strpos($key, 'HTTP_') === 0) {
                $name = strtr(strtolower(substr($key, 5)), '_', '-');
                $headers[$name] = $value;
                continue;
            }

            if ($value && strpos($key, 'CONTENT_') === 0) {
                $name = 'content-'.strtolower(substr($key, 8));
                $headers[$name] = $value;
                continue;
            }
        }

        return $headers;
    }

    /**
     * Parse a cookie header according to RFC 6265.
     *
     * PHP will replace special characters in cookie names, which results in other cookies not being available due to
     * overwriting. Thus, the server request should take the cookies from the request header instead.
     *
     * @param string $cookieHeader A string cookie header value.
     *
     * @return array key/value cookie pairs.
     */
    public static function parseCookieHeader($cookieHeader)
    {
        preg_match_all('(
        (?:^\\n?[ \t]*|;[ ])
        (?P<name>[!#$%&\'*+-.0-9A-Z^_`a-z|~]+)
        =
        (?P<DQUOTE>"?)
            (?P<value>[\x21\x23-\x2b\x2d-\x3a\x3c-\x5b\x5d-\x7e]*)
        (?P=DQUOTE)
        (?=\\n?[ \t]*$|;[ ])
    )x', $cookieHeader, $matches, PREG_SET_ORDER);

        $cookies = [];

        foreach ($matches as $match) {
            $cookies[$match['name']] = urldecode($match['value']);
        }

        return $cookies;
    }

    /**
     * Marshal a Uri instance based on the values presnt in the $_SERVER array and headers.
     *
     * @param array $server
     * @param array $headers
     *
     * @throws \Apli\Uri\UriException
     *
     * @return Url
     */
    public static function marshalUriFromSapi(array $server, array $headers)
    {
        /**
         * Retrieve a header value from an array of headers using a case-insensitive lookup.
         *
         * @param string $name
         * @param array  $headers Key/value header pairs
         * @param mixed  $default Default value to return if header not found
         *
         * @return mixed
         */
        $getHeaderFromArray = function ($name, array $headers, $default = null) {
            $header = strtolower($name);
            $headers = array_change_key_case($headers, CASE_LOWER);
            if (array_key_exists($header, $headers)) {
                $value = is_array($headers[$header]) ? implode(', ', $headers[$header]) : $headers[$header];

                return $value;
            }

            return $default;
        };

        /**
         * Marshal the host and port from HTTP headers and/or the PHP environment.
         *
         * @param array $headers
         * @param array $server
         *
         * @return array Array of two items, host and port, in that order (can be
         *               passed to a list() operation).
         */
        $marshalHostAndPort = function (array $headers, array $server) use ($getHeaderFromArray) {
            /**
             * @param string|array $host
             *
             * @return array Array of two items, host and port, in that order (can be
             *               passed to a list() operation).
             */
            $marshalHostAndPortFromHeader = function ($host) {
                if (is_array($host)) {
                    $host = implode(', ', $host);
                }

                $port = null;

                // works for regname, IPv4 & IPv6
                if (preg_match('|\:(\d+)$|', $host, $matches)) {
                    $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
                    $port = (int) $matches[1];
                }

                return [$host, $port];
            };

            /**
             * @param array    $server
             * @param string   $host
             * @param null|int $port
             *
             * @return array Array of two items, host and port, in that order (can be
             *               passed to a list() operation).
             */
            $marshalIpv6HostAndPort = function (array $server, $host, $port) {
                $host = '['.$server['SERVER_ADDR'].']';
                $port = $port ?: 80;
                if ($port.']' === substr($host, strrpos($host, ':') + 1)) {
                    // The last digit of the IPv6-Address has been taken as port
                    // Unset the port so the default port can be used
                    $port = null;
                }

                return [$host, $port];
            };

            static $defaults = ['', null];

            if ($getHeaderFromArray('host', $headers, false)) {
                return $marshalHostAndPortFromHeader($getHeaderFromArray('host', $headers));
            }

            if (!isset($server['SERVER_NAME'])) {
                return $defaults;
            }

            $host = $server['SERVER_NAME'];
            $port = isset($server['SERVER_PORT']) ? (int) $server['SERVER_PORT'] : null;

            if (!isset($server['SERVER_ADDR'])
                || !preg_match('/^\[[0-9a-fA-F\:]+\]$/', $host)
            ) {
                return [$host, $port];
            }

            // Misinterpreted IPv6-Address
            // Reported for Safari on Windows
            return $marshalIpv6HostAndPort($server, $host, $port);
        };

        /**
         * Detect the path for the request.
         *
         * Looks at a variety of criteria in order to attempt to autodetect the base
         * request path, including:
         *
         * - IIS7 UrlRewrite environment
         * - REQUEST_URI
         * - ORIG_PATH_INFO
         *
         * From ZF2's Zend\Http\PhpEnvironment\Request class
         *
         * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
         * @license   http://framework.zend.com/license/new-bsd New BSD License
         *
         * @param array $server SAPI environment array (typically `$_SERVER`)
         *
         * @return string Discovered path
         */
        $marshalRequestPath = function (array $server) {
            // IIS7 with URL Rewrite: make sure we get the unencoded url
            // (double slash problem).
            $iisUrlRewritten = array_key_exists('IIS_WasUrlRewritten', $server) ? $server['IIS_WasUrlRewritten'] : null;
            $unencodedUrl = array_key_exists('UNENCODED_URL', $server) ? $server['UNENCODED_URL'] : '';
            if ('1' === $iisUrlRewritten && !empty($unencodedUrl)) {
                return $unencodedUrl;
            }

            $requestUri = array_key_exists('REQUEST_URI', $server) ? $server['REQUEST_URI'] : null;

            if ($requestUri !== null) {
                return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
            }

            $origPathInfo = array_key_exists('ORIG_PATH_INFO', $server) ? $server['ORIG_PATH_INFO'] : null;
            if (empty($origPathInfo)) {
                return '/';
            }

            return $origPathInfo;
        };

        $uri = Url::createFromString('');

        // URI scheme
        $scheme = 'http';
        if (array_key_exists('HTTPS', $server)) {
            $https = $server['HTTPS'];
        } elseif (array_key_exists('https', $server)) {
            $https = $server['https'];
        } else {
            $https = false;
        }
        if (($https && 'off' !== strtolower($https))
            || strtolower($getHeaderFromArray('x-forwarded-proto', $headers, false)) === 'https'
        ) {
            $scheme = 'https';
        }

        // Set the host
        list($host, $port) = $marshalHostAndPort($headers, $server);
        if (!empty($host)) {
            $uri = $uri->withHost($host);
            if (!empty($port)) {
                $uri = $uri->withPort($port);
            }
        }

        // URI path
        $path = $marshalRequestPath($server);

        // Strip query string
        $path = explode('?', $path, 2)[0];

        // URI query
        $query = '';
        if (isset($server['QUERY_STRING'])) {
            $query = ltrim($server['QUERY_STRING'], '?');
        }

        // URI fragment
        $fragment = '';
        if (strpos($path, '#') !== false) {
            list($path, $fragment) = explode('#', $path, 2);
        }

        return $uri
            ->withScheme($scheme)
            ->withPath($path)
            ->withFragment($fragment)
            ->withQuery($query);
    }

    /**
     * Retrieve the request method from the SAPI parameters.
     *
     * @param array $server
     *
     * @return string
     */
    public static function marshalMethodFromSapi(array $server)
    {
        return isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : 'GET';
    }

    /**
     * Return HTTP protocol version (X.Y) as discovered within a `$_SERVER` array.
     *
     * @param array $server
     *
     * @throws UnexpectedValueException if the $server['SERVER_PROTOCOL'] value is
     *                                  malformed.
     *
     * @return string
     */
    public static function marshalProtocolVersionFromSapi(array $server)
    {
        if (!isset($server['SERVER_PROTOCOL'])) {
            return '1.1';
        }

        if (!preg_match('#^(HTTP/)?(?P<version>[1-9]\d*(?:\.\d)?)$#', $server['SERVER_PROTOCOL'], $matches)) {
            throw new UnexpectedValueException(sprintf(
                'Unrecognized protocol version (%s)',
                $server['SERVER_PROTOCOL']
            ));
        }

        return $matches['version'];
    }

    /**
     * Access a value in an array, returning a default value if not found.
     *
     * @deprecated since 1.8.0; no longer used internally.
     *
     * @param string $key
     * @param array  $values
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($key, array $values, $default = null)
    {
        if (array_key_exists($key, $values)) {
            return $values[$key];
        }

        return $default;
    }

    /**
     * Search for a header value.
     *
     * Does a case-insensitive search for a matching header.
     *
     * If found, it is returned as a string, using comma concatenation.
     *
     * If not, the $default is returned.
     *
     * @deprecated since 1.8.0; no longer used internally.
     *
     * @param string $header
     * @param array  $headers
     * @param mixed  $default
     *
     * @return string
     */
    public static function getHeader($header, array $headers, $default = null)
    {
        $header = strtolower($header);
        $headers = array_change_key_case($headers, CASE_LOWER);
        if (array_key_exists($header, $headers)) {
            $value = is_array($headers[$header]) ? implode(', ', $headers[$header]) : $headers[$header];

            return $value;
        }

        return $default;
    }

    /**
     * Strip the query string from a path.
     *
     * @deprecated since 1.8.0; no longer used internally.
     *
     * @param mixed $path
     *
     * @return string
     */
    public static function stripQueryString($path)
    {
        return explode('?', $path, 2)[0];
    }
}
