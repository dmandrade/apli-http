<?php
/**
 *  Copyright (c) 2019 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 *  @project apli
 *  @file ServerRequestCreatorInterface.php
 *  @author Danilo Andrade <danilo@webbingbrasil.com.br>
 *  @date 17/11/19 at 18:14
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 17/11/2019
 * Time: 18:14
 */

namespace Apli\Http;

use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request as SwooleHttpRequest;

interface ServerRequestCreatorInterface
{

    /**
     * Create a new server request from the current environment variables.
     *
     * @throws \InvalidArgumentException if no valid method or URI can be determined
     */
    public function fromGlobals(): ServerRequestInterface;

    /**
     * Create a new request from Swoole Server
     *
     * @param SwooleHttpRequest $request
     * @return ServerRequestInterface
     */
    public function fromSwoole(SwooleHttpRequest $request): ServerRequestInterface;

    /**
     * Create a new server request from a set of arrays.
     *
     * @param array $server
     * @param array $headers
     * @param array $cookie
     * @param array $get
     * @param array $post
     * @param array $files
     * @param null  $body
     * @return ServerRequestInterface
     */
    public function fromArrays(
        array $server,
        array $headers = [],
        array $cookie = [],
        array $get = [],
        array $post = [],
        array $files = [],
        $body = null
    ): ServerRequestInterface;

    /**
     * Get parsed headers from ($_SERVER) array.
     *
     * @param array $server typically $_SERVER or similar structure
     *
     * @return array
     */
    public static function getHeadersFromServer(array $server): array;
}
