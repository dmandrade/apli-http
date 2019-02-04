<?php
/**
 *  Copyright (c) 2018 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file EmitterException.php
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 04/09/18 at 12:05
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 04/09/2018
 * Time: 12:05
 */

namespace Apli\Http\Exception;

use RuntimeException;

class EmitterException extends RuntimeException
{
    /**
     * @return EmitterException
     */
    public static function forHeadersSent()
    {
        return new self('Unable to emit response; headers already sent');
    }

    /**
     * @return EmitterException
     */
    public static function forOutputSent()
    {
        return new self('Output has been emitted previously; cannot emit response');
    }
}
