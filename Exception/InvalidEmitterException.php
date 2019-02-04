<?php
/**
 *  Copyright (c) 2018 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file InvalidEmitterException.php
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 04/09/18 at 10:52
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 04/09/2018
 * Time: 10:52
 */

namespace Apli\Http\Exception;

use Apli\Http\Emitter\EmitterInterface;
use Apli\Http\Emitter\EmitterStack;
use InvalidArgumentException;

class InvalidEmitterException extends InvalidArgumentException
{
    /**
     * @var mixed $emitter Invalid emitter type
     */
    public static function forEmitter($emitter)
    {
        return new self(sprintf(
            '%s can only compose %s implementations; received %s',
            EmitterStack::class,
            EmitterInterface::class,
            is_object($emitter) ? get_class($emitter) : gettype($emitter)
        ));
    }
}
