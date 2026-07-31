<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/SoUuid
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\SoUuid;

use DateTimeImmutable;

/**
 * interface SoUuidInterface
 */
interface SoUuidInterface
{
    public function decode(): array;

    public function getBytes(): string;

    public function getHex(): string;

    public function getIdentifier(): string;

    public function getString(): string;

    public function getMicroTime(): string;

    public function getDateTime(): DateTimeImmutable;

    public function getBase62(): string;

    public function getBase36(): string;
}
