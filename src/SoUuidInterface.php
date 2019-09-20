<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/SoUuid
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
    public function decode(): array;

    /**
     * @return string
     */
    public function getBytes(): string;

    /**
     * @return string
     */
    public function getHex(): string;

    /**
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @return string
     */
    public function getString(): string;

    /**
     * @return string
     */
    public function getMicroTime(): string;

    /**
     * @return \DateTimeImmutable
     */
    public function getDateTime(): \DateTimeImmutable;

    /**
     * @return string
     */
    public function getBase62(): string;

    /**
     * @return string
     */
    public function getBase36(): string;
}
