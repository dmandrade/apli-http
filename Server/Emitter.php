<?php
/**
 *  Copyright (c) 2018 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 *  @project apli
 *  @file Emitter.php
 *  @author Danilo Andrade <danilo@webbingbrasil.com.br>
 *  @date 04/09/18 at 09:29
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 04/09/2018
 * Time: 09:29
 */

namespace Apli\Http\Server;


use Apli\Http\Message\Response;

interface Emitter
{

    /**
     * Emits a response, including status line, headers, and the message body,
     * according to the environment.
     *
     * @param Response $response
     * @return bool
     */
    public function emit(Response $response);
}
