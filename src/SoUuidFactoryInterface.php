<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/SoUuid
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
     * @param string|int|null $identifier
     *
     * @return SoUuidInterface
     */
    public static function generate($identifier = null): SoUuidInterface;

    /**
     * @param string $uuidString
     *
     * @return SoUuidInterface
     */
    public static function fromString(string $uuidString): SoUuidInterface;

    /**
     * @param string $uuidHex
     *
     * @return SoUuidInterface
     */
    public static function fromHex(string $uuidHex): SoUuidInterface;

    /**
     * @param string $uuidBytes
     *
     * @return SoUuidInterface
     */
    public static function fromBytes(string $uuidBytes): SoUuidInterface;

    /**
     * @param string $uuidString
     *
     * @return SoUuidInterface
     */
    public static function fromBase62(string $uuidString): SoUuidInterface;

    /**
     * @param string $uuidString
     *
     * @return SoUuidInterface
     */
    public static function fromBase36(string $uuidString): SoUuidInterface;
}
