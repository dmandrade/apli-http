<?php
/**
 *  Copyright (c) 2018 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 *  @project apli
 *  @file UriFactory.php
 *  @author Danilo Andrade <danilo@webbingbrasil.com.br>
 *  @date 02/09/18 at 17:30
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 02/09/2018
 * Time: 17:30
 */

namespace Apli\Http\Message;

interface UriFactory
{
    /**
     * Create a new URI.
     *
     * @param string $uri
     *
     * @return Uri
     *
     * @throws \InvalidArgumentException If the given URI cannot be parsed.
     */
    public function createUri($uri = '');
}
