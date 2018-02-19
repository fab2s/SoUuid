<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\SoUuid\Tests;

use fab2s\SoUuid\SoUuid;
use fab2s\SoUuid\SoUuidInterface;

class SoUuidTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    protected $identifiers = [
        '',
        null,
        0,
        "\n",
        'abc',
        'я',
        1337,
        'a27b28',
    ];

    public function testGenerate()
    {
        $input    = [];
        $inputHex = [];

        foreach ($this->identifiers as $identifier) {
            for ($i = 0; $i < 100; ++$i) {
                $uuid       = SoUuid::generate($identifier);
                $input[]    = $uuid->getBytes();
                $inputHex[] = $uuid->getHex();
                $this->assertNotEquals(SoUuid::generate($identifier)->getHex(), SoUuid::generate($identifier)->getHex());
                $this->assertNotEquals(SoUuid::generate($identifier)->getString(), SoUuid::generate($identifier)->getString());
                $this->assertNotEquals(SoUuid::generate($identifier)->getBytes(), SoUuid::generate($identifier)->getBytes());
            }
        }

        $sorted = $input;
        sort($sorted);
        $this->assertSame($input, $sorted);

        $sortedHex = $inputHex;
        sort($sortedHex);

        $this->assertSame($inputHex, $sortedHex);

        $uuid = SoUuid::generate($identifier);
        $this->assertSame(bin2hex($uuid->getBytes()), $uuid->getHex());
        $this->assertSame(bin2hex($uuid->getBytes()), str_replace('-', '', $uuid->getString()));
    }

    /**
     * @return array
     */
    public function uuidProvider()
    {
        $data = [];
        foreach ($this->identifiers as $identifier) {
            $uuid                = SoUuid::generate($identifier);
            $decoded             = $uuid->decode();
            $decoded['dateTime'] = $decoded['dateTime']->getTimestamp();
            $data[]              = [
                'uuid'       => $uuid,
                'decoded'    => $decoded,
                'identifier' => $identifier,
            ];
        }

        return $data;
    }

    /**
     * @dataProvider uuidProvider
     *
     * @param SoUuidInterface $uuid
     * @param array           $decoded
     * @param string|null     $identifier
     */
    public function testDecode(SoUuidInterface $uuid, $decoded, $identifier)
    {
        $this->assertSame((string) $identifier, $uuid->getIdentifier());
        $this->assertSame($decoded['dateTime'], (int) floor($uuid->getMicroTime() / 1000000));
    }

    /**
     * @dataProvider uuidProvider
     *
     * @param SoUuidInterface $uuid
     * @param array           $decoded
     */
    public function testFromBytes(SoUuidInterface $uuid, $decoded)
    {
        $this->assertSame(SoUuid::fromBytes($uuid->getBytes())->getHex(), $uuid->getHex());
        $this->assertSame(SoUuid::fromBytes($uuid->getBytes())->getHex(), $uuid->getHex());
        $this->assertSame(SoUuid::fromBytes($uuid->getBytes())->getString(), $uuid->getString());

        $reDecoded = SoUuid::fromBytes($uuid->getBytes())->decode();
        $this->assertInstanceOf('\DateTimeImmutable', $reDecoded['dateTime']);
        $reDecoded['dateTime'] = $reDecoded['dateTime']->getTimestamp();

        $this->assertSame($reDecoded, $decoded);
    }

    /**
     * @dataProvider uuidProvider
     *
     * @param SoUuidInterface $uuid
     * @param array           $decoded
     */
    public function testFromString(SoUuidInterface $uuid, $decoded)
    {
        $this->assertSame(SoUuid::fromString($uuid->getString())->getHex(), $uuid->getHex());
        $this->assertSame(SoUuid::fromString($uuid->getString())->getHex(), $uuid->getHex());
        $this->assertSame(SoUuid::fromString($uuid->getString())->getString(), $uuid->getString());

        $reDecoded = SoUuid::fromString($uuid->getString())->decode();
        $this->assertInstanceOf('\DateTimeImmutable', $reDecoded['dateTime']);
        $reDecoded['dateTime'] = $reDecoded['dateTime']->getTimestamp();

        $this->assertSame($reDecoded, $decoded);
    }

    /**
     * @dataProvider uuidProvider
     *
     * @param SoUuidInterface $uuid
     * @param array           $decoded
     */
    public function testFromHex(SoUuidInterface $uuid, $decoded)
    {
        $this->assertSame(SoUuid::fromHex($uuid->getHex())->getHex(), $uuid->getHex());
        $this->assertSame(SoUuid::fromHex($uuid->getHex())->getHex(), $uuid->getHex());
        $this->assertSame(SoUuid::fromHex($uuid->getHex())->getString(), $uuid->getString());

        $reDecoded = SoUuid::fromHex($uuid->getHex())->decode();
        $this->assertInstanceOf('\DateTimeImmutable', $reDecoded['dateTime']);
        $reDecoded['dateTime'] = $reDecoded['dateTime']->getTimestamp();

        $this->assertSame($reDecoded, $decoded);
    }
}
