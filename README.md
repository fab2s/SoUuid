# SoUuid : Simple Ordered UUID 

[![Build Status](https://travis-ci.com/fab2s/SoUuid.svg?branch=master)](https://travis-ci.com/fab2s/SoUuid) [![Total Downloads](https://poser.pugx.org/fab2s/souuid/downloads)](https://packagist.org/packages/fab2s/souuid) [![Monthly Downloads](https://poser.pugx.org/fab2s/souuid/d/monthly)](https://packagist.org/packages/fab2s/souuid) [![Latest Stable Version](https://poser.pugx.org/fab2s/souuid/v/stable)](https://packagist.org/packages/fab2s/souuid)  [![Maintainability](https://api.codeclimate.com/v1/badges/14b58f95d46d0d2d47a7/maintainability)](https://codeclimate.com/github/fab2s/SoUuid/maintainability) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fab2s/SoUuid/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fab2s/SoUuid/?branch=master) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat)](http://makeapullrequest.com) [![License](https://poser.pugx.org/fab2s/nodalflow/license)](https://packagist.org/packages/fab2s/souuid)

`SoUuid` is a working proposal to generate ordered UUIDs in a simple and efficient way using PHP. 

While UUIDs already have [well defined standards](https://tools.ietf.org/html/rfc4122), they suffer from quite bad performance when used as a primary key in DBMS. The reason are well know and goes down to :  
- UUID are 36 characters long, which can grow the index size significantly, especially with InnoDB and alike where every secondary index would also contain the primary key.
- Insert comes at a terrible cost since UUID PKs are pretty random thus highly scattered across the index.

You can find more information and benchmarks in [Percona Database Performance Blog](https://www.percona.com/blog/2014/12/19/store-uuid-optimized-way/) or [on MariaDb's KB](https://mariadb.com/kb/en/library/guiduuid-performance/). Both includes solutions to handle the matter at the database level. While they focus on Mysql, the problem is similar with other DBMS : lack of UUID order and UUID size are costly, with ordering being critical for massive inserts on a PK.

The order problem has been addressed in various ways in PHP, most being implemented as some extra feature of an RFC compliant implementation. It seemed to me that something simpler and more adequate with PHP could be of some use. Because to start with, PHP is limited to the micro-second, and RFC implementation have to artificially meet the "official" 100ns interval, which actually weakens the uniqueness of the UUID, as the same RFC random bits are now protecting a ten times wider interval against collisions.

It's of course ok to trade some performance and even some usability to stay in standards, but all together it also seems weird to be stuck with such an inefficient format that does not even match the level of guarantees defined by the original RFC. Then, using the Gregorian calendar as origin of time just brings nothing to the table. Using the Epoch time seems more reasonable and more convenient to work with. Unless you where planning to simulate pre-70's Mac address (did they even exist before that), it's just a waist of data space.

While it may have made more sens at some point to bind UUIDs to some physical Mac Address for eternity, it is quite less obvious now as hardware has become a commodity and that Mac addresses are subject to change frequently with no other meaning than "a deployment was made". So it seems a bit awkward to have to deal with Mac Address format just to store a more meaningful id, especially with PHP where Mac address is a rather distant information. It's also kind of a limitation to be bound to any particular format for something that should belong to the application space, like a worker or job id.

So all together it felt like there was a room for some simple improvements that hopefully will help out in real life situations.

## Installation

`SoUuid` can be installed using composer:

```
composer require "fab2s/souuid"
```

If you want to specifically install the php >=7.1.0 version, use:

```
composer require "fab2s/souuid" ^1
```

If you want to specifically install the php 5.6/7.0 version, use:

```
composer require "fab2s/souuid" ^0
```

There are no changes other than further typing from 0.x to 1.x

## In practice 

### Without identifier

```php
$uuid = SoUuid::generate();
$uuid->getBytes(); // 16 bytes binary string b"\x05d¦U<Ÿ¾\x00:F°(ÛEa\x07"
$uuid->getHex(); // "0564a6553c9fbe003a46b028db456107"
$uuid->getDateTime(); // DateTimeImmutable 2018-02-07 21:54:01.0 +00:00
$uuid->getMicroTime(); // 1518040440.938430
$uuid->getIdentifier(); // ""
$uuid->getString(); // "0564a6553c9fbe-003a-46b0-28db-456107"
$uuid->decode(); // lazy generated array representation
/*
array:4 [
  "microTime" => "1518040440.938430"
  "dateTime" => DateTimeImmutable @1518040441 {#14
    date: 2018-02-07 21:54:01.0 +00:00
  }
  "identifier" => ""
  "rand" => "3a46b028db456107"
]
*/
```

The string format does not match RFC pattern to prevent any confusion. But it's still matching the storage requirement of the RFC in every way for better portability : 36 chars string or 16 bytes binary string, also being the most efficient option.

### With an identifier :

```php
$uuid = SoUuid::generate('w12345'); 
$uuid->getIdentifier(); // "w12345"
$uuid->decode();
/*
array:4 [
  "microTime" => "1518040333.014178"
  "dateTime" => DateTimeImmutable @1518040333 {#14
    date: 2018-02-07 21:52:13.0 +00:00
  }
  "identifier" => "w12345"
  "rand" => "7cea2b"
]
*/
```

You get binary 16 : 
> b"\x05d¦NÍÔ¢<b>w12345</b>|ê+"

### Building from Strings

You can easily instantiate UUIDs from string, hex and binary form :

```php
$uuid = SoUuid::fromString('0564a64ecdd4a2-7731-3233-3435-7cea2b'); 
$uuid = SoUuid::fromHex('0564a64ecdd4a27731323334357cea2b'); 
$uuid = SoUuid::fromBytes(b"\x05d¦NÍÔ¢w12345|ê+");
$uuid->decode();
/*
array:4 [
  "microTime" => "1518040333.014178"
  "dateTime" => DateTimeImmutable @1518040333 {#14
    date: 2018-02-07 21:52:13.0 +00:00
  }
  "identifier" => "w12345"
  "rand" => "7cea2b"
]
*/
```

### Base62

`SoUuid` supports a `base62` format based on [gmp](http://php.net/gmp) which can be a handy form to expose to HTTP interfaces and URLs:

```php
SoUuid::generate()->getBase62(); // ABRxdU5wbCLM7E7QhHS6r
$uuid = SoUuid::fromBase62('ABRxdU5wbCLM7E7QhHS6r');
```

Base62 `SoUuids` are variable length to a maximum of 22 chars within SoUuid valid time frame. They are fully ordered to the micro second if you left pad them with **0** up to the max length.

```php
$orderedBase62Uuid =  str_pad(SoUuid::generate()->getBase62(), 22, '0', STR_PAD_LEFT);
```

If you start generating now, base62 UUIDs will have a length of 21 chars until the 2398-12-22 05:49:06 UTC (base 62 zzzzzzzzz = 13 537 086 546 263 551 µsec or 13 537 086 546 epoch time). This should leave enough time to think about left padding old UUIDs in case preserving a consistent ordering still matters at that point.

This makes `base62` format the second most efficient format for PK after the raw binary form. At the cost of 5 (or 6 if you have plans for year 2400) more characters, you get a more friendly format, ready to be used basically anywhere with no further transformation (url compatible etc) _except_ where case is insensitive. For DBMS, it's easy to make sure the PK field is case sensitive (binary or ascii_bin), but you cannot use these in filename on windows systems as the file system is case insensitive and that would open a gate to collisions.

In such case, `base36` format may be a better option.

### Base36

Following the same spirit, `SoUuid` provides with a `base36` alternative to `base62`, again based on [gmp](http://php.net/gmp). 

```php
SoUuid::generate()->getBase36(); // bix20qgjqmi9hqxh0y9tao5u
$uuid = SoUuid::fromBase36('bix20qgjqmi9hqxh0y9tao5u');
```

At the cost of an increased max length of 25 characters, the format becomes case insensitive. It is still ordered within the whole SoUuid time frame when properly padded :

```php
$orderedBase36Uuid =  str_pad(SoUuid::generate()->getBase36(), 25, '0', STR_PAD_LEFT);
```

If you start generating now, `base36` UUIDs will have a length of 24 chars until the 2085-11-09 15:34:00 UTC (base 36 zzzzzzzzzz = 3 656 158 440 062 975 µsec or 3 656 158 440 epoch time). This still leaves some time to think about left padding old UUIDs in case preserving a consistent ordering still matters at that point.

All together, this makes base36 format the third in efficiency as PK. You get a friendly ordered format, as portable as the regular UUID formats (case insensitive) at the cost of three more bytes compared to base62 while still preserving 11 bytes compared to the RFC formats.

## Laravel (the awesome)

You can `use` `SoUuidTrait` directly in your models to start using SoUuid's as primary keys in your models.

By default, it will use the raw binary form, also being the most effective, but you can also use any other form by overriding the `generateSoUuid` method in your model (or in an abstract or trait using this trait) :

```php
    /**
     * @throws \Exception
     *
     * @return string
     */
    public static function generateSoUuid(): string
    {
        // base62 example, could be any of the available forms
        return SoUuid::generate(static::generateSoUuidIdentifier())->getBase62();
    }
```

> Note that you can manually any identifier that may be more suitable for you than the default one provided by `generateSoUuidIdentifier` which will be derived from the `ModelName` using each of the capitalized letters (up to 6, eg `mn` from `ModelName`).

### Migrations 

In any form, the best type for the database field carrying the SoUuid should be ascii case insensitive and match the target length of the chosen type :

```php 
// Raw binary form
$table->char('id', 16)->charset('ascii')->collation('ascii_bin')->primary();

// base62 unpaded, valid until 2398-12-22 05:49:06 UTC
$table->char('id', 21)->charset('ascii')->collation('ascii_bin')->primary();

// base36 unpaded, valid until 2085-11-09 15:34:00 UTC
$table->char('id', 24)->charset('ascii')->collation('ascii_bin')->primary();

// string form, valid until 4253-05-31 22:20:37 UTC
$table->char('id', 36)->charset('ascii')->collation('ascii_bin')->primary();
```

All of which being fully ordered to the microsecond.

## Behind the scene

`SoUuid` aims at being a simple and efficient with a high level of protection against collisions.

The recipe is pretty basic and is mostly inspired by the original RFC:
- The current time to the micro second is stored in 56-bit binary format (7 bytes). 7 bytes is one byte bellow the RFC for the 100ns Gregorian time, but it is enough to encode microsecond timestamps until **4253-05-31 22:20:37 UTC** (or 2^56 microsecond after unix epoch - 1 µs).
- Following the RFC spirit, 6 bytes are then reserved for an identifier, similar to the RFC `node` parameter, except this identifier can be any 6 or bellow bytes, not necessarily an hex Mac address'ish string.
- Again like the RFC, some random bytes are finally added, but since we saved one from the time part, both by limiting validity span for the next 2 millenniums and reducing the precision to micro seconds, one more random byte can be picked.

The result is a 16 bytes binary string ready to be used as primary key and ordered to the microsecond.
This means that only the inserts generated within the same micro second may not be directly happened to the primary index. If this should happen, it should still be better to insert a row near the top of the index rather that in the middle or worst. 

Then, the custom identifier slot is added before the random part because, while we are at it, this can help out a bit when scanning for your custom identifiers on the primary index, as they will be found earlier in the string.

If you do not provide with an identifier, 5 out of the 6 reserved bytes will be randomly picked and left padded with a null byte to remember it was random. If you use less than 6 bytes for the identifier, a null byte is right padded, and the eventual remaining gap is filled with random bytes. So you just have to worry about not using a null byte in your identifiers and to limit them to 6 bytes. For example, `"abc1"` will be encoded as `b"abc1\x00Ü"` (including one extra byte of entropy or 256 more combinations) in the binary uuid string and retrieved as exactly `"abc1"` when decoded.

So altogether this means that we are left with one chance out of 2^24 (= 16 777 216) to collide within the same micro second in the WORST and _insane_ case where only one identifier of exactly 6 bytes would be used everywhere. 
Without any identifier, you add 5 random bytes and reach a total of 8 random bytes (2^64 combinations) to prevent collision within the same micro second, and this matches the best PHP RFC implementations.
Of course, using meaningful and efficient identifiers such as a worker ids can reduce the chances to collide down to none. Just like with other RFC implementations, you still have to manually set the identifier, which otherwise defaults to a random string.

`SoUuid` has no opinion about the identifier to use, these 6 bytes can be 6 alphanumerical chars like `w99j42` or a big integer converted to base 16 binary. For example 4 bytes can encode a decimal integer up to 2^32 - 1 or 4 294 967 295 which is already pretty big for the purpose of limiting collisions within one microsecond. With the full 6 bytes, you get 2^48 or 281 474 976 710 656, but I mean, how many UUID generators/worker do you actually use at once ?

```php
hex2bin(base_convert("4294967295", 10, 16)); // b"ÿÿÿÿ"
```

You can even use some of these bytes to add a touch of [nonce](https://en.wikipedia.org/wiki/Cryptographic_nonce). Could just be round robin'd over a decent max int and shared among workers on the same host with [apcu](http://php.net/apcu), or even by specifying loop ranges for each server to use for their workers and so on.

There is plenty of room to implement something that does the job in many particular cases. The identifier details are owned by their generator while still being part of a standard than can be shared and used by every `SoUuid` generator. It could even make sense to port the recipe to other languages if at least part of your UUIDs are PHP generated. You would just trade a slightly smaller time window for easier identifier control which ultimately is the best guarantee against collisions.

## Why waist one entire null byte ?

Well, as you may have noticed, a null byte (`\0`) is used to distinguish random identifier from user defined identifier. It's pretty clear that some _headache_ could be invested into using fewer space for that purpose. 
But, at the very least and with no identifier, `SoUuid` still uses 8 bytes of entropy to protect each microseconds, and this _at least_ matches the best _theoretical_ level obtained with existing PHP implementations of the RFC.

I say _theoretical_ because this implies that these implementations actually gather microseconds, which does not seems so obvious when you look at the details.

This comes from the fact that [microtime(1)](http://php.net/microtime) is returning a `float` which is indeed limited by the php.ini `precision` directive, defaulting to 14:

```
; The number of significant digits displayed in floating point numbers.
; http://php.net/precision
precision = 14
```

So by default you get :

```php
microtime(1); // 1517992591.8068
```

While the same micro-time would be available with actual micro-time precision as a string :

```php
microtime(0); // "0.80684200 1517992591"
```

So this means that in practice you may find yourself with lower precisions when you use code based on `microtime(1)` to generate UUIDs. With an actual default of a 1000x wider time window to be protected against collisions compared to the RFC.

`SoUuid` uses a string based approach that actually maintain the microsecond precision:

```php
$timeParts = explode(' ', microtime(false));
/*
[
 "0.30693800", // there seems to always be two extra 0 after the µs
 "1517993957",
]
*/

$timeMicroSec = $timeParts[1] . substr($timeParts[0], 2, 6); // 1517993957306938
```

So my current thinking is that, in addition to not being such a XOR myself, this is probably a good initial stand. Because in situations where huge amounts of UUIDs are to be generated, you need to properly use identifiers anyway, and this was already made easier. This room could also be used later to extend and version the `SoUuid` format (please be my `1337` ^^).

Ultimately, you can use `random_bytes(6)` as default identifier if you do not want to generate identifiers and still want to get the whole 9 bytes of entropy protecting each micro second and lower the collision probability to the pretty insane value of one out of 2^72 = 4 722 366 482 869 645 213 696 for UUIDs generated within the same micro-second.

As [random_compat](https://github.com/paragonie/random_compat) is included as polyfill for [random_bytes()](http://php.net/random_bytes), you don't even have to worry about getting good randomness bellow PHP 7.

## Performance

Since the [issue](https://github.com/fab2s/SoUuid/issues/1) was raised, I included a simple benchmark script to compare UUID generation time with [Webpaster/Uuid](https://github.com/webpatser/laravel-uuid) and [Ramsey/Uuid](https://github.com/ramsey/uuid). 
Note that these libs are both good at doing their job and can be trusted in production environment. Besides, UUID generation time never was the actual performance issue since we are here talking about fractions of a second per 100K UUIDs where the scattered insert could end up costing hours in practice. It's also rather normal to find out that compared implementations, which handles 4 UUID versions and tend to go as deep in RFC as possible, are a bit slower. The main point stays the same : the slow part is not the implementation, it's the RFC's lack of order and the way it is used (string vs binary form and ordering, [benchmarks here](https://www.percona.com/blog/2014/12/19/store-uuid-optimized-way/)).

In addition, the comparison is not entirely honest since all implementations do not do exactly the same thing to end up with a comparable UUID string. For example `Webpatser/Uuid` does pre-compute the string representation right after binary generation, and `SoUuid` does not. With `SoUuid`, Hex and String forms are lazy generated. But this does not slow down actual "insert after generate" either, because the binary form is the root from which other forms are derived, and it is the one to use for best performances as PK.

Anyway, if you still bother:

```
$ composer install --dev
$ php bench
```

The bench script is untested bellow php 7.

**PHP 7.1.2:**

```
Benchmarking fab2s/SoUuid vs Ramsey/Uuid vs Webpatser/Uuid
Iterations: 100 000
Averaged over: 10
PHP 7.1.2 (cli) (built: Feb 14 2017 21:24:45) ( NTS MSVC14 (Visual C++ 2015) x64 )
Copyright (c) 1997-2017 The PHP Group
Zend Engine v3.1.0, Copyright (c) 1998-2017 Zend Technologies
Windows NT xxxxx 10.0 build 16299 (Windows 10) AMD64
+----------------+----------+-----------+---------+
| Generator      | Time (s) | Delta (s) | %       |
+----------------+----------+-----------+---------+
| fab2s/SoUuid   | 0.4533   |           |         |
| Webpatser/Uuid | 0.9050   | 0.4517    | 99.63%  |
| Ramsey/Uuid    | 1.5755   | 1.1221    | 247.52% |
+----------------+----------+-----------+---------+

Time: 29.43 seconds, Memory: 2.00MB
```

**PHP 7.2.0:**

```
Benchmarking fab2s/SoUuid vs Ramsey/Uuid vs Webpatser/Uuid
Iterations: 100 000
Averaged over: 10
PHP 7.2.0 (cli) (built: Nov 28 2017 23:48:32) ( NTS MSVC15 (Visual C++ 2017) x64 )
Copyright (c) 1997-2017 The PHP Group
Zend Engine v3.2.0, Copyright (c) 1998-2017 Zend Technologies
Windows NT xxxxx 10.0 build 16299 (Windows 10) AMD64
+----------------+----------+-----------+---------+
| Generator      | Time (s) | Delta (s) | %       |
+----------------+----------+-----------+---------+
| fab2s/SoUuid   | 0.3421   |           |         |
| Webpatser/Uuid | 0.6919   | 0.3498    | 102.26% |
| Ramsey/Uuid    | 1.3497   | 1.0076    | 294.57% |
+----------------+----------+-----------+---------+

Time: 23.92 seconds, Memory: 4.00MB
```

It seems like the only interesting fact we can learn from this is that PHP 7.2.0 is faster than PHP 7.1.2 at the cost of more memory usage.

## Requirements

`SoUuid` is tested against php 7.1, 7.2, 7.3, 7.4, 8.0 and 8.1

## Contributing

Contributions are welcome, do not hesitate to open issues and submit pull requests.

## License

`SoUuid` is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
