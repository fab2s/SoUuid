<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/SoUuid
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\SoUuid\Laravel;

use fab2s\SoUuid\SoUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait SoUuidTrait
{
    /**
     * @var []
     */
    protected static $soUuidIdentifiers;

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * @throws \Exception
     *
     * @return string
     */
    public static function generateSoUuid(): string
    {
        return SoUuid::generate(static::generateSoUuidIdentifier())->getString();
    }

    /**
     * Boot Laravel model
     *
     * @throws \Exception
     */
    protected static function bootSoUuidTrait()
    {
        // saving fires before creating / updating which gives
        // them opportunity to use the eventual new id before update
        static::saving(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = static::generateSoUuid();
            }
        });
    }

    /**
     * @return string
     */
    protected static function generateSoUuidIdentifier(): string
    {
        if (isset(static::$soUuidIdentifiers[static::class])) {
            return static::$soUuidIdentifiers[static::class];
        }

        $modelName = class_basename(static::class);
        if (strlen($modelName) <= 6) {
            return $modelName;
        }

        // MyModelName to mmn
        $parts  = explode('_', Str::snake($modelName));
        $result = [];
        foreach ($parts as $part) {
            $result[] = $part[0];
        }

        return static::$soUuidIdentifiers[static::class] = implode('', array_slice($result, 0, 6));
    }
}
