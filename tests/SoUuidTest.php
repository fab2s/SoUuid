<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\SoUuid\Tests;

use fab2s\SoUuid\SoUuid;

class SoUuidTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array
     */
    public function generateData()
    {
        return [
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
            [SoUuid::generate()],
        ];
    }

    /**
     * @dataProvider generateData
     *
     * @param SoUuid $uuid
     */
    public function testGenerate(SoUuid $uuid)
    {
        $bytes    = $uuid->getBytes();
        $input    = [$bytes];
        $inputHex = [$uuid->getHex()];

        for ($i=0; $i < 100; ++$i) {
            $input[]    = SoUuid::generate();
            $inputHex[] = SoUuid::generate()->getHex();
            $this->assertNotEquals($bytes, SoUuid::generate());
            $this->assertNotEquals(SoUuid::generate(), SoUuid::generate());
        }

        $sorted = $input;
        sort($sorted);
        $this->assertSame($input, $sorted);

        $sortedHex = $inputHex;
        sort($sortedHex);

        $this->assertSame($inputHex, $sortedHex);
    }
}
