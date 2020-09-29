<?php
/**
 *  Copyright (c) 2018 Danilo Andrade.
 *
 *  This file is part of the apli project.
 *
 * @project apli
 * @file EmitterStack.php
 *
 * @author Danilo Andrade <danilo@webbingbrasil.com.br>
 * @date 04/09/18 at 10:47
 */

/**
 * Created by PhpStorm.
 * User: Danilo
 * Date: 04/09/2018
 * Time: 10:47.
 */

namespace Apli\Http\Emitter;

use Apli\Data\Stack;
use Apli\Http\Exception\InvalidEmitterException;
use Psr\Http\Message\ResponseInterface;

class EmitterStack extends Stack implements EmitterInterface
{

    /**
     * {@inheritdoc}
     */
    public function emit(ResponseInterface $response): void
    {
        foreach ($this as $emitter) {
            /** @var EmitterInterface $emitter */
            $emitter->emit($response);
        }
    }

    /**
     * Set an emitter on the stack by index.
     *
     * @param mixed            $index
     * @param EmitterInterface $emitter
     *
     * @throws InvalidArgumentException if not an EmitterInterface instance
     *
     * @return void
     */
    public function offsetSet($index, $emitter): void
    {
        $this->prepareEmitter((array) $emitter);
        parent::offsetSet($index, $emitter);
    }

    /**
     * Validate that an emitter implements EmitterInterface.
     *
     * @param EmitterInterface[] $emitters
     *
     * @throws InvalidEmitterException for non-emitter instances
     */
    private function prepareEmitter($emitters): void
    {
        foreach ($emitters as $emitter) {
            if (!$emitter instanceof EmitterInterface) {
                throw InvalidEmitterException::forEmitter($emitter);
            }
        }
    }

    /**
     * Push an emitter to the stack.
     *
     * @param ...EmitterInterface $emitters
     *
     * @throws InvalidArgumentException if not an EmitterInterface instance
     *
     * @return void
     */
    public function push(...$emitters): void
    {
        $this->prepareEmitter($emitters);
        parent::push(...$emitters);
    }
}
