<?php
/**
 *  Copyright (c) 2018 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 *  @project apli
 *  @file RequestHandler.php
 *  @author Danilo Andrade <danilo@webbingbrasil.com.br>
 *  @date 02/09/18 at 17:08
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 02/09/2018
 * Time: 17:08
 */

namespace Apli\Http\Server;

use Apli\Http\Message\Response;
use Apli\Http\Message\ServerRequest;

/**
 * An HTTP request handler process a HTTP request and produces an HTTP response.
 * This interface defines the methods required to use the request handler.
 */
interface RequestHandler
{
    /**
     * Handle the request and return a response.
     *
     * @param ServerRequest $request
     * @return Response
     */
    public function handle(ServerRequest $request);
}
