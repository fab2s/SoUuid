<?php

/*
 * This file is part of SoUuid.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/SoUuid
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\SoUuid\Tests;

use fab2s\SoUuid\Laravel\SoUuidBase62Trait;
use fab2s\SoUuid\Laravel\SoUuidTrait;
use fab2s\SoUuid\SoUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\CoversTrait;

/**
 * Short enough a name to be used as identifier as is
 */
class Short extends Model
{
    use SoUuidTrait;
    protected $table   = 'shorts';
    public $timestamps = false;
    protected $guarded = [];
}

/**
 * Too long a name, initials are used instead
 */
class MyLongModelName extends Model
{
    use SoUuidTrait;
    protected $table   = 'longs';
    public $timestamps = false;
    protected $guarded = [];
}

class Base62Model extends Model
{
    use SoUuidBase62Trait;
    protected $table   = 'base62s';
    public $timestamps = false;
    protected $guarded = [];
}

#[CoversTrait(SoUuidTrait::class)]
#[CoversTrait(SoUuidBase62Trait::class)]
class SoUuidLaravelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['shorts', 'longs', 'base62s'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name')->nullable();
            });
        }
    }

    public function test_key_is_not_incrementing()
    {
        $model = new Short;

        $this->assertFalse($model->getIncrementing());
        $this->assertSame('string', $model->getKeyType());
    }

    public function test_uuid_is_generated_on_save()
    {
        $model = new Short;
        $model->save();

        $this->assertNotEmpty($model->id);
        $this->assertSame($model->id, SoUuid::fromString($model->id)->getString());
        // short class names are used as identifier as is
        $this->assertSame('Short', SoUuid::fromString($model->id)->getIdentifier());
    }

    public function test_long_model_name_is_reduced_to_initials()
    {
        $first = new MyLongModelName;
        $first->save();

        // MyLongModelName -> my_long_model_name -> mlmn
        $this->assertSame('mlmn', SoUuid::fromString($first->id)->getIdentifier());

        // second one hits the memoized identifier
        $second = new MyLongModelName;
        $second->save();

        $this->assertSame('mlmn', SoUuid::fromString($second->id)->getIdentifier());
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_base62_uuid_is_generated_on_save()
    {
        $model = new Base62Model;
        $model->save();

        $this->assertNotEmpty($model->id);
        $this->assertSame($model->id, SoUuid::fromBase62($model->id)->getBase62());
        // Base62Model -> base62_model -> bm
        $this->assertSame('bm', SoUuid::fromBase62($model->id)->getIdentifier());
    }

    public function test_existing_key_is_left_untouched()
    {
        $uuid = SoUuid::generate('kept')->getString();

        $model     = new Short;
        $model->id = $uuid;
        $model->save();

        $this->assertSame($uuid, $model->fresh()->id);
    }

    public function test_generate_so_uuid_is_publicly_usable()
    {
        $this->assertSame('Short', SoUuid::fromString(Short::generateSoUuid())->getIdentifier());
        $this->assertSame('bm', SoUuid::fromBase62(Base62Model::generateSoUuid())->getIdentifier());
    }
}
