<?php
/**
 *  Copyright (c) 2018 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 *  @project apli
 *  @file RequestFactory.php
 *  @author Danilo Andrade <danilo@webbingbrasil.com.br>
 *  @date 02/09/18 at 17:21
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 02/09/2018
 * Time: 17:21
 */

namespace Apli\Http\Message;

interface RequestFactory
{
    /**
     * Create a new request.
     *
     * @param string $method The HTTP method associated with the request.
     * @param Uri|string $uri The URI associated with the request. If
     *     the value is a string, the factory MUST create a UriInterface
     *     instance based on it.
     *
     * @return Request
     */
    public function createRequest($method, $uri);
}
