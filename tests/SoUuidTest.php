<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/SoUuid
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

    /**
     * @throws \Exception
     */
    public function testGenerate()
    {
        $input    = [];
        $inputHex = [];
        $inputB62 = [];
        $inputB36 = [];

        // remove the irrelevant portion of the order test
        // as within the same micro second, order may change
        $randTail    = random_bytes(9);
        $randTailHex = bin2hex($randTail);

        // base 62 will use one more char after 2398-12-22 05:49:06
        $randTailB62      = str_pad(gmp_strval(gmp_init($randTailHex, 16), 62), 12, '0', STR_PAD_LEFT);
        $b62TimeSwitch    = time() > 13537086546;
        $base62Length     = $b62TimeSwitch ? 22 : 21;
        $base62TimeLength = $b62TimeSwitch ? 10 : 9;

        // base 36 will use one more char after 2085-11-09 15:34:00
        $randTailB36      = str_pad(gmp_strval(gmp_init($randTailHex, 16), 36), 14, '0', STR_PAD_LEFT);
        $b36TimeSwitch    = time() > 3656158440;
        $base36Length     = $b36TimeSwitch ? 25 : 24;
        $base36TimeLength = $b36TimeSwitch ? 11 : 10;
        foreach ($this->identifiers as $identifier) {
            for ($i = 0; $i < 100; ++$i) {
                $uuid       = SoUuid::generate($identifier);
                $input[]    = substr($uuid->getBytes(), 0, 7) . $randTail;
                $inputHex[] = substr($uuid->getHex(), 0, 14) . $randTailHex;
                $inputB62[] = substr($uuid->getHex(), 0, $base62TimeLength) . $randTailB62;
                $inputB36[] = substr($uuid->getHex(), 0, $base36TimeLength) . $randTailB36;

                $this->assertNotEquals(SoUuid::generate($identifier)->getHex(), SoUuid::generate($identifier)->getHex());
                $this->assertNotEquals(SoUuid::generate($identifier)->getString(), SoUuid::generate($identifier)->getString());
                $this->assertNotEquals(SoUuid::generate($identifier)->getBytes(), SoUuid::generate($identifier)->getBytes());
                $this->assertNotEquals(SoUuid::generate($identifier)->getBase62(), SoUuid::generate($identifier)->getBase62());
                $this->assertNotEquals(SoUuid::generate($identifier)->getBase36(), SoUuid::generate($identifier)->getBase36());
            }

            $this->assertSame(16, strlen($uuid->getBytes()));
            $this->assertSame($base62Length, strlen($uuid->getBase62()));
            $this->assertSame($base36Length, strlen($uuid->getBase36()));
            $this->assertSame(32, strlen($uuid->getHex()));
            $this->assertSame(36, strlen($uuid->getString()));

            $this->assertSame(bin2hex($uuid->getBytes()), $uuid->getHex());
            $this->assertSame(bin2hex($uuid->getBytes()), str_replace('-', '', $uuid->getString()));
            $this->assertSame(SoUuid::generate($identifier)->getDateTime()->format('Y-m-D H:i:s'), (new \DateTimeImmutable('@' . time()))->format('Y-m-D H:i:s'));
        }

        $sorted = $input;
        sort($sorted);
        $this->assertSame($input, $sorted);

        $sortedHex = $inputHex;
        sort($sortedHex);
        $this->assertSame($inputHex, $sortedHex);

        $sortedB62 = $inputB62;
        sort($sortedB62);
        $this->assertSame($inputB62, $sortedB62);

        $sortedB36 = $inputB36;
        sort($sortedB36);
        $this->assertSame($inputB36, $sortedB36);
    }

    /**
     * @throws \Exception
     *
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
     * @param string|int|null $identifier (scalar would be a more accurate type)
     */
    public function testDecode(SoUuidInterface $uuid, array $decoded, $identifier)
    {
        $this->assertSame((string) $identifier, $uuid->getIdentifier());
        $this->assertSame($decoded['dateTime'], (int) substr($uuid->getMicroTime(), 0, -6));
    }

    /**
     * @dataProvider uuidProvider
     *
     * @param SoUuidInterface $uuid
     * @param array           $decoded
     */
    public function testFromBytes(SoUuidInterface $uuid, array $decoded)
    {
        $this->assertSame(SoUuid::fromBytes($uuid->getBytes())->getBytes(), $uuid->getBytes());
        $this->assertSame(SoUuid::fromBytes($uuid->getBytes())->getHex(), $uuid->getHex());
        $this->assertSame(SoUuid::fromBytes($uuid->getBytes())->getString(), $uuid->getString());
        $this->assertSame(SoUuid::fromBytes($uuid->getBytes())->getBase62(), $uuid->getBase62());
        $this->assertSame(SoUuid::fromBytes($uuid->getBytes())->getBase36(), $uuid->getBase36());

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
    public function testFromString(SoUuidInterface $uuid, array $decoded)
    {
        $this->assertSame(SoUuid::fromString($uuid->getString())->getBytes(), $uuid->getBytes());
        $this->assertSame(SoUuid::fromString($uuid->getString())->getHex(), $uuid->getHex());
        $this->assertSame(SoUuid::fromString($uuid->getString())->getString(), $uuid->getString());
        $this->assertSame(SoUuid::fromString($uuid->getString())->getBase62(), $uuid->getBase62());
        $this->assertSame(SoUuid::fromString($uuid->getString())->getBase36(), $uuid->getBase36());

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
    public function testFromHex(SoUuidInterface $uuid, array $decoded)
    {
        $this->assertSame(SoUuid::fromHex($uuid->getHex())->getBytes(), $uuid->getBytes());
        $this->assertSame(SoUuid::fromHex($uuid->getHex())->getHex(), $uuid->getHex());
        $this->assertSame(SoUuid::fromHex($uuid->getHex())->getString(), $uuid->getString());
        $this->assertSame(SoUuid::fromHex($uuid->getHex())->getBase62(), $uuid->getBase62());
        $this->assertSame(SoUuid::fromHex($uuid->getHex())->getBase36(), $uuid->getBase36());

        $reDecoded = SoUuid::fromHex($uuid->getHex())->decode();
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
    public function testFromBase62(SoUuidInterface $uuid, array $decoded)
    {
        $this->assertSame(SoUuid::fromBase62($uuid->getBase62())->getBytes(), $uuid->getBytes());
        $this->assertSame(SoUuid::fromBase62($uuid->getBase62())->getHex(), $uuid->getHex());
        $this->assertSame(SoUuid::fromBase62($uuid->getBase62())->getString(), $uuid->getString());
        $this->assertSame(SoUuid::fromBase62($uuid->getBase62())->getBase62(), $uuid->getBase62());
        $this->assertSame(SoUuid::fromBase62($uuid->getBase62())->getBase36(), $uuid->getBase36());

        $reDecoded = SoUuid::fromHex($uuid->getHex())->decode();
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
    public function testFromBase36(SoUuidInterface $uuid, array $decoded)
    {
        $this->assertSame(SoUuid::fromBase36($uuid->getBase36())->getBytes(), $uuid->getBytes());
        $this->assertSame(SoUuid::fromBase36($uuid->getBase36())->getHex(), $uuid->getHex());
        $this->assertSame(SoUuid::fromBase36($uuid->getBase36())->getString(), $uuid->getString());
        $this->assertSame(SoUuid::fromBase36($uuid->getBase36())->getBase62(), $uuid->getBase62());
        $this->assertSame(SoUuid::fromBase36($uuid->getBase36())->getBase36(), $uuid->getBase36());

        $reDecoded = SoUuid::fromHex($uuid->getHex())->decode();
        $this->assertInstanceOf('\DateTimeImmutable', $reDecoded['dateTime']);
        $reDecoded['dateTime'] = $reDecoded['dateTime']->getTimestamp();

        $this->assertSame($reDecoded, $decoded);
    }

    /**
     * @throws \Exception
     */
    public function testEncodeIdentifier()
    {
        $this->assertSame(6, strlen(SoUuid::encodeIdentifier()));
        for ($i=0; $i < 10; ++$i) {
            $this->assertSame(6, strlen(SoUuid::encodeIdentifier(str_repeat('#', $i))));
        }
    }
}
