<?php
/**
 *  Copyright (c) 2018 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 *  @project apli
 *  @file ResponseFactory.php
 *  @author Danilo Andrade <danilo@webbingbrasil.com.br>
 *  @date 02/09/18 at 17:25
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 02/09/2018
 * Time: 17:25
 */

namespace Apli\Http\Message;

interface ResponseFactory
{
    /**
     * Create a new response.
     *
     * @param int $code HTTP status code; defaults to 200
     * @param string $reasonPhrase Reason phrase to associate with status code
     *     in generated response; if none is provided implementations MAY use
     *     the defaults as suggested in the HTTP specification.
     *
     * @return Response
     */
    public function createResponse($code = 200, $reasonPhrase = '');
}
