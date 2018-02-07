<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\SoUuid;

/**
 * interface SoUuidInterface
 */
interface SoUuidInterface
{
    /**
     * @return array
     */
    public function decode();

    /**
     * @return string
     */
    public function getBytes();

    /**
     * @return string
     */
    public function getHex();

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getString();

    /**
     * @return string
     */
    public function getMicroTime();

    /**
     * @return \DateTimeImmutable
     */
    public function getDateTime();
}
