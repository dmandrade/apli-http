<?php
/**
 *  Copyright (c) 2018 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 *  @project apli
 *  @file SapiEmitter.php
 *  @author Danilo Andrade <danilo@webbingbrasil.com.br>
 *  @date 04/09/18 at 12:04
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 04/09/2018
 * Time: 12:04
 */

namespace Apli\Http\Emitter;


use Apli\Http\Message\Response;
use Apli\Http\Server\Emitter;

class SapiEmitter implements Emitter
{
    use SapiEmitterTrait;

    /**
     * Emits a response for a PHP SAPI environment.
     *
     * Emits the status line and headers via the header() function, and the
     * body content via the output buffer.
     */
    public function emit(Response $response)
    {
        $this->assertNoPreviousOutput();
        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        $this->emitBody($response);
        return true;
    }

    /**
     * Emit the message body.
     * @param Response $response
     */
    private function emitBody(Response $response)
    {
        echo $response->getBody();
    }
}
