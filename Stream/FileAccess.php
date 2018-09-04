<?php
/**
 *  Copyright (c) 2018 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 *  @project apli
 *  @file StreamMode.php
 *  @author Danilo Andrade <danilo@webbingbrasil.com.br>
 *  @date 04/09/18 at 08:44
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 04/09/2018
 * Time: 08:44
 */

namespace Apli\Http\Stream;


use Apli\Support\AbstractEnum;

/**
 * Class FileAccess
 * @package Apli\Http\Stream
 */
class FileAccess extends AbstractEnum
{
    const __default = self::READ_FROM_BEGIN;

    const READ_FROM_BEGIN = 'r';
    const WRITE_FROM_BEGIN = 'w';
    const READ_WRITE_FROM_BEGIN=  'w+';
    const WRITE_FROM_END = 'a';
    const READ_WRITE_FROM_END = 'a+';
    const BINARY_READ_FROM_BEGIN = 'rb';
    const BINARY_WRITE_FROM_BEGIN = 'wb';
    const BINARY_READ_WRITE_FROM_BEGIN = 'wb+';
    const BINARY_WRITE_FROM_END = 'ab';
    const BINARY_READ_WRITE_FROM_END = 'ab+';
}
