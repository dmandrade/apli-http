<?php
/**
 *  Copyright (c) 2018 Danilo Andrade
 *
 *  This file is part of the apli project.
 *
 *  @project apli
 *  @file StreamFactory.php
 *  @author Danilo Andrade <danilo@webbingbrasil.com.br>
 *  @date 02/09/18 at 17:27
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 02/09/2018
 * Time: 17:27
 */

namespace Apli\Http\Message;

interface StreamFactory
{
    /**
     * Create a new stream from a string.
     *
     * The stream SHOULD be created with a temporary resource.
     *
     * @param string $content String content with which to populate the stream.
     *
     * @return Stream
     */
    public function createStream($content = '');

    /**
     * Create a stream from an existing file.
     *
     * The file MUST be opened using the given mode, which may be any mode
     * supported by the `fopen` function.
     *
     * The `$filename` MAY be any string supported by `fopen()`.
     *
     * @param string $filename Filename or stream URI to use as basis of stream.
     * @param string $mode Mode with which to open the underlying filename/stream.
     *
     * @return Stream
     */
    public function createStreamFromFile($filename, $mode = 'r');

    /**
     * Create a new stream from an existing resource.
     *
     * The stream MUST be readable and may be writable.
     *
     * @param resource $resource PHP resource to use as basis of stream.
     *
     * @return Stream
     */
    public function createStreamFromResource($resource);
}
