<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/SoUuid
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\SoUuid\Laravel;

use fab2s\SoUuid\SoUuid;

trait SoUuidBase62Trait
{
    use SoUuidTrait;

    /**
     * @throws \Exception
     *
     * @return string
     */
    public static function generateSoUuid(): string
    {
        return SoUuid::generate(static::generateSoUuidIdentifier())->getBase62();
    }
}
