<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\SoUuid;

/**
 * interface SoUuidFactoryInterface
 */
interface SoUuidFactoryInterface
{
    /**
     * @param null $identifier
     *
     * @return SoUuidInterface
     */
    public static function generate($identifier = null);

    /**
     * @param string $uuidString
     *
     * @return SoUuidInterface
     */
    public static function fromString($uuidString);

    /**
     * @param string $uuidHex
     *
     * @return SoUuidInterface
     */
    public static function fromHex($uuidHex);

    /**
     * @param string $uuidBytes
     *
     * @return SoUuidInterface
     */
    public static function fromBytes($uuidBytes);
}
