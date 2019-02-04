<?php
/**
 *  Copyright (c) 2018 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file SapiEmitter.php
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 04/09/18 at 12:04
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 04/09/2018
 * Time: 12:04
 */

namespace Apli\Http\Emitter;

use Psr\Http\Message\ResponseInterface;

class SapiEmitter extends AbstractSapiEmitter
{
    /**
     * {@inheritdoc}
     */
    public function emit(ResponseInterface $response)
    {
        $this->assertNoPreviousOutput();
        $this->emitHeaders($response);
        $this->emitStatusLine($response);
        $this->emitBody($response);
        $this->closeConnection();
    }

    /**
     * Emit the message body.
     * @param ResponseInterface $response
     */
    private function emitBody(ResponseInterface $response)
    {
        echo $response->getBody();
    }
}
