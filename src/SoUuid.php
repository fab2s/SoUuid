<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/SoUuid
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\SoUuid;

/**
 * class SoUuid
 */
class SoUuid implements SoUuidInterface, SoUuidFactoryInterface
{
    /**
     * The identifier separator, used to handle variable length
     */
    const IDENTIFIER_SEPARATOR = "\0";

    /**
     * String format
     */
    const UUID_REGEX = '`^[0-9a-f]{14}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{6}$`i';

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var \DateTimeImmutable
     */
    protected $dateTime;

    /**
     * @var array
     */
    protected $decoded;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $string;

    /**
     * @var string
     */
    protected $microTime;

    /**
     * @var string
     */
    protected $base62;

    /**
     * @var string
     */
    protected $base36;

    /**
     * SoUuid constructor.
     *
     * @param string $uuid
     */
    protected function __construct(string $uuid)
    {
        $this->uuid = (string) $uuid;
    }

    /**
     * @param string|int|null $identifier
     *
     * @throws \Exception
     *
     * @return SoUuidInterface
     */
    public static function generate($identifier = null): SoUuidInterface
    {
        // 7 bit micro-time
        $uuid = static::microTimeBin();
        // 6 bytes identifier
        $uuid .= static::encodeIdentifier($identifier);
        // 3 random bytes (2^24 = 16 777 216 combinations)
        $uuid .= random_bytes(3);

        return new static($uuid);
    }

    /**
     * @param string $uuidString
     *
     * @throws \InvalidArgumentException
     *
     * @return SoUuidInterface
     */
    public static function fromString(string $uuidString): SoUuidInterface
    {
        if (!preg_match(static::UUID_REGEX, $uuidString)) {
            throw new \InvalidArgumentException('Uuid String is not valid');
        }

        return new static(hex2bin(str_replace('-', '', $uuidString)));
    }

    /**
     * @param string $uuidString
     *
     * @throws \InvalidArgumentException
     *
     * @return SoUuidInterface
     */
    public static function fromHex(string $uuidString): SoUuidInterface
    {
        if (!preg_match('`^[0-9a-f]{32}$`i', $uuidString)) {
            throw new \InvalidArgumentException('Uuid Hex String is not valid');
        }

        return new static(hex2bin($uuidString));
    }

    /**
     * @param string $uuidString
     *
     * @throws \InvalidArgumentException
     *
     * @return SoUuidInterface
     */
    public static function fromBytes(string $uuidString): SoUuidInterface
    {
        if (strlen($uuidString) !== 16) {
            throw new \InvalidArgumentException('Uuid Binary String must be of length 16');
        }

        return new static($uuidString);
    }

    /**
     * @param string $uuidString
     *
     * @throws \InvalidArgumentException
     *
     * @return SoUuidInterface
     */
    public static function fromBase62(string $uuidString): SoUuidInterface
    {
        if (!ctype_alnum($uuidString)) {
            throw new \InvalidArgumentException('Uuid Base62 String must composed of a-zA-z0-9 exclusively');
        }

        $hex = gmp_strval(gmp_init($uuidString, 62), 16);

        return new static(hex2bin(str_pad($hex, 32, '0', STR_PAD_LEFT)));
    }

    /**
     * @param string $uuidString
     *
     * @throws \InvalidArgumentException
     *
     * @return SoUuidInterface
     */
    public static function fromBase36(string $uuidString): SoUuidInterface
    {
        if (!ctype_alnum($uuidString)) {
            throw new \InvalidArgumentException('Uuid Base36 String must composed of a-z0-9 exclusively');
        }

        $hex = gmp_strval(gmp_init($uuidString, 36), 16);

        return new static(hex2bin(str_pad($hex, 32, '0', STR_PAD_LEFT)));
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function decode(): array
    {
        if ($this->decoded === null) {
            $idLen         = strlen($this->getIdentifier());
            $this->decoded = [
                'microTime'  => $this->getMicroTime(),
                'dateTime'   => $this->getDateTime(),
                'identifier' => $this->getIdentifier(),
                'rand'       => bin2hex(substr($this->uuid, $idLen ? 7 + $idLen : 8)),
            ];
        }

        return $this->decoded;
    }

    /**
     * @return string
     */
    public function getBytes(): string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getHex(): string
    {
        return bin2hex($this->uuid);
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        if ($this->identifier === null) {
            $this->identifier  = substr($this->uuid, 7, 6);
            $identifierNullPos = strpos($this->identifier, static::IDENTIFIER_SEPARATOR);
            if ($identifierNullPos !== false) {
                // set to empty string if the identifier was random
                // as it starts with static::IDENTIFIER_SEPARATOR
                $this->identifier = substr($this->identifier, 0, $identifierNullPos);
            }
        }

        return $this->identifier;
    }

    /**
     * The string format does not match RFC pattern to prevent any confusion in this form.
     * It's still mimicking the 36 char length to match the storage requirement of the RFC
     * in every way : 36 char string or 16 bytes binary string, also being the most efficient
     *
     * @return string
     */
    public function getString(): string
    {
        if ($this->string === null) {
            // microsecond epoch - 2/6 id bytes - 4/6 id bytes - 6/6 id bytes - 3 random bytes
            $hex          = $this->getHex();
            $this->string = substr($hex, 0, 14) . '-' .
                substr($hex, 14, 4) . '-' .
                substr($hex, 18, 4) . '-' .
                substr($hex, 22, 4) . '-' .
                substr($hex, 26);
        }

        return $this->string;
    }

    /**
     * @return string
     */
    public function getMicroTime(): string
    {
        if ($this->microTime === null) {
            $timeBin         = substr($this->uuid, 0, 7);
            $this->microTime = base_convert(bin2hex($timeBin), 16, 10);
        }

        return $this->microTime;
    }

    /**
     * @throws \Exception
     *
     * @return \DateTimeImmutable
     */
    public function getDateTime(): \DateTimeImmutable
    {
        if ($this->dateTime === null) {
            $this->dateTime = new \DateTimeImmutable('@' . substr($this->getMicroTime(), 0, -6));
        }

        return $this->dateTime;
    }

    /**
     * @return string
     */
    public function getBase62(): string
    {
        if ($this->base62 === null) {
            // max SoUuid = max microtime . max rem bits = 2^56 . 2^72 = 72057594037927936 . 4722366482869645213696
            // max SoUuid = 720575940379279364722366482869645213696 = GUvfO1q6dEMruD35q5aZKi in base 62 (22 chars)
            $this->base62 = gmp_strval(gmp_init(bin2hex($this->uuid), 16), 62);
        }

        return $this->base62;
    }

    /**
     * @return string
     */
    public function getBase36(): string
    {
        if ($this->base36 === null) {
            // max SoUuid = 720575940379279364722366482869645213696 = w3dfhtoz4u26q89wgfzwnz94w in base 36 (25 chars)
            $this->base36 = gmp_strval(gmp_init(bin2hex($this->uuid), 16), 36);
        }

        return $this->base36;
    }

    /**
     * @return string
     */
    public static function microTimeBin(): string
    {
        // get real microsecond precision, as both microtime(1) and array_sum(explode(' ', microtime()))
        // are limited by php.ini precision
        $timeParts    = explode(' ', microtime(false));
        $timeMicroSec = $timeParts[1] . substr($timeParts[0], 2, 6);
        // convert to 56-bit integer (7 bytes), enough to store micro time is enough up to 4253-05-31 22:20:37
        $time = base_convert($timeMicroSec, 10, 16);
        // left pad the eventual gap
        return hex2bin(str_pad($time, 14, '0', STR_PAD_LEFT));
    }

    /**
     * @param string|int|null $identifier
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public static function encodeIdentifier($identifier = null): string
    {
        if ($identifier !== null) {
            if (strpos($identifier, static::IDENTIFIER_SEPARATOR) !== false) {
                throw new \InvalidArgumentException('SoUuid identifiers cannot contain ' . bin2hex(static::IDENTIFIER_SEPARATOR));
            }

            $len        = strlen($identifier);
            $identifier = substr($identifier, 0, 6) . ($len <= 4 ? static::IDENTIFIER_SEPARATOR . random_bytes(5 - $len) : '');

            return str_pad($identifier, 6, static::IDENTIFIER_SEPARATOR, STR_PAD_RIGHT);
        }

        return static::IDENTIFIER_SEPARATOR . random_bytes(5);
    }
}
