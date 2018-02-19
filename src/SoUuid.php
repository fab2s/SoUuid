<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
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
     * SoUuid constructor.
     *
     * @param string $uuid
     */
    protected function __construct($uuid)
    {
        $this->uuid = (string) $uuid;
    }

    /**
     * @param string|null $identifier
     *
     * @return SoUuidInterface
     */
    public static function generate($identifier = null)
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
     * @return SoUuidInterface
     */
    public static function fromString($uuidString)
    {
        if (!preg_match(static::UUID_REGEX, $uuidString)) {
            throw new \InvalidArgumentException('Uuid String is not valid');
        }

        $uuidParts = explode('-', $uuidString);

        return new static(implode('', array_map('hex2bin', $uuidParts)));
    }

    /**
     * @param string $uuidString
     *
     * @return SoUuidInterface
     */
    public static function fromHex($uuidString)
    {
        if (!preg_match('`^[0-9a-f]{32}$`i', $uuidString)) {
            throw new \InvalidArgumentException('Uuid Hex String is not valid');
        }

        return new static(hex2bin($uuidString));
    }

    /**
     * @param string $uuidString
     *
     * @return SoUuidInterface
     */
    public static function fromBytes($uuidString)
    {
        if (strlen($uuidString) !== 16) {
            throw new \InvalidArgumentException('Uuid Binary String must be of length 16');
        }

        return new static($uuidString);
    }

    /**
     * @return array
     */
    public function decode()
    {
        if ($this->decoded === null) {
            $idLen         = strlen($this->getIdentifier());
            $this->decoded = [
                'microTme'   => $this->getMicroTime(),
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
    public function getBytes()
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getHex()
    {
        return bin2hex($this->uuid);
    }

    /**
     * @return string
     */
    public function getIdentifier()
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
    public function getString()
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
    public function getMicroTime()
    {
        if ($this->microTime === null) {
            $timeBin         = substr($this->uuid, 0, 7);
            $this->microTime = hexdec(bin2hex($timeBin));
        }

        return $this->microTime;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDateTime()
    {
        if ($this->dateTime === null) {
            $this->dateTime = new \DateTimeImmutable('@' . (int) floor($this->getMicroTime() / 1000000));
        }

        return $this->dateTime;
    }

    /**
     * @return string
     */
    public static function microTimeBin()
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
     * @param string|null $identifier
     *
     * @return string
     */
    public static function encodeIdentifier($identifier = null)
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
