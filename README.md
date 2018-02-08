# SoUuid : Simple Ordered UUID 

[![Build Status](https://travis-ci.org/fab2s/NodalFlow.svg?branch=master)](https://travis-ci.org/fab2s/NodalFlow)

SoUuid is a working proposal to generate ordered UUIDs is a simple and efficient way in PHP.

While UUID already have [well defined standards](https://tools.ietf.org/html/rfc4122), they suffer from quite bad performance when used a primary index in DBMS. The reason are well know and goes down to :  
- UUID being 36 characters long, which can grow the index size significantly, especially with InnoDB and alike where every secondary index would also contain the primary key.
- Insert comes at a terrible cost since UUID PKs are pretty random thus highly scattered across the index.

You can find more information and benchmarks in [Percona Database Performance Blog](https://www.percona.com/blog/2014/12/19/store-uuid-optimized-way/) or [on MariaDb's KB](https://mariadb.com/kb/en/library/guiduuid-performance/). Both includes solutions to handle the matter at the database level. While they focus on Mysql, the problem is similar with other DBMS : lack of UUID order and UUID size are costly, with ordering being critical for massive inserts on a PK.

The order problem has been addressed in various ways in PHP, most being implemented as some extra feature of an RFC compliant implementation. It seemed to me that something simpler and more adequate with PHP could be of some use.

Because to start with, PHP is limited to the micro-second, and RFC implementation have to artificially meet the "official" 100ns interval, which actually weakens the uniqueness of the UUID, as the same RFC random bits are now protecting a ten times wider interval against collisions.

It's of course ok to trade some performance and even some usability to stay in standards, but all together it also seems weird to be stuck with such an inefficient format which in practice cannot even match the level of guarantees against collision provided by the original RFC. Now about using the Gregorian calendar as origin of time, it just brings nothing to the table. Using the Epoch time seems more reasonable and more convenient to work with. Unless you where planning to simulate pre-70's MAC address (did they even exist before that), it's just a waist of data space.

It may have made more sens at some point to bind UUIDs to some physical Mac Address for eternity, but now that hardware has become a commodity, Mac address are subject to change frequently with no other meaning than "a deployment was done". And MAc address is rather a distant information in PHP. So it seems a bit awkward to have to deal with Mac Address format just to store a worker id. It's also kind of a limitation to be bound to any particular format for something that should belong to the application space like a worker or job id.

So all together it felt like there was a room for some simple improvements that hopefully will help out in real life situations.

## Installation

SoUuid can be installed using composer :

```
composer require "fab2s/souuid"
```

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
  "microTme" => "1518040440.938430"
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
  "microTme" => "1518040333.014178"
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
  "microTme" => "1518040333.014178"
  "dateTime" => DateTimeImmutable @1518040333 {#14
    date: 2018-02-07 21:52:13.0 +00:00
  }
  "identifier" => "w12345"
  "rand" => "7cea2b"
]
*/
```

## Behind the scene

The proposed implementation aim at being a simple and efficient UUID solution for PHP language with a high level of protection against collisions.

The recipe is pretty basic and is mostly inspired by the original RFC:
- The current time to the micro second is stored in 56-bit binary format (7 bytes). 7 bytes is one byte bellow the RFC for the 100ns time but it is enough to encode microsecond timestamps until 4253-05-31 22:20:37 (or 2^56 microsecond after unix epoch - 1 µs).
- Following the RFC spirit, 6 bytes are then reserved for an identifier, similar to the RFC `node` parameter, except this identifier can be any 6 or bellow bytes, not necessarily an hex MAC address'ish string.
- Again like the RFC, some random bytes are finally added, but since we saved one from the time part, both by limiting validity span for the next 2 millenniums and reducing the length to micro seconds, one more random byte can be picked.

The result is a 16 bytes binary string ready to be used as primary key and ordered to the microsecond.
This means that only the inserts generated within the same micro second may not be directly happened to the primary index. If this should happen, it would still be better to insert a row near the top of the index rather that in the middle or worst. 

Then, the custom identifier slot is added before the random part because, while we are at it, this can help out a bit when scanning for your custom identifiers on the primary index, as they will be found earlier in the string.

If you do not provide with an identifier, 5 out of the 6 reserved bytes will be randomly picked and left padded with a null byte to remember it was random. If you use less than 6 bytes for the identifier, a null byte is right padded, and the eventual remaining gap is filled with random bytes. So you just have to worry about not using a null byte in you identifiers and to limit them to 6 bytes. For example, `"abc1"` will be encoded as `b"abc1\x00Ü"` (including one extra byte of entropy or 256 more combinations) in the binary uuid string and retrieved as exactly `"abc1"` when decoded.

So altogether this means that we are left with one chance out of 2^24 (= 16 777 216) to collide within the same micro second in the WORST and insane case where only one identifier of exactly 6 bytes would be used everywhere. 
Without any identifier, you add 5 random bytes (one out of 2^40 = 1 099 511 627 776) and reach a total of 8 random bytes (2^64 combinations) to prevent collision within the same micro second, which matches the best PHP RFC implementations.
Of course, using meaningful and efficient identifiers such as a worker ids can reduce the chances to collide down to none. Just like with other RFC implementations, you still have to manually set the identifier, which otherwise defaults to a random string.

SoUuid has no opinion about the identifier to use, these 6 bytes can be 6 alphanumerical chars like `w99j42` or a big integer converted to base 16 binary. For example 4 bytes can encode a decimal integer up to 2^32 - 1 or 4 294 967 295 which is already pretty big for the purpose of limiting collisions within one microsecond. With the full 6 bytes, you get 2^48 or 281 474 976 710 656, but I mean, how many UUID generators/worker do you actually use at once ?

```php
hex2bin(base_convert("4294967295", 10, 16)); // b"ÿÿÿÿ"
```

or for smaller numbers in the same range :

```php
// left padd zeros to the base 16 integer representation to fill the resulting 4 bytes space
hex2bin(str_pad(base_convert("1337", 10, 16), 8, "0", STR_PAD_LEFT)); // "\0\0\x059"
```

You can even use some of these bytes to add a touch of [nonce](https://en.wikipedia.org/wiki/Cryptographic_nonce). Could just be round robin'd over a decent max int and shared among workers on the same host with [apcu](http://php.net/apcu), or even by specifying loop ranges for each server to use for its workers and so on.

There is plenty of room to implement something that does the job in many particular cases. The identifier details are owned by their generator while still being part of a standard than can be shared and used by every SoUuid generator. It could even make sense to port the recipe to other languages if at least part of your UUIDs are PHP generated. You would just trade a slightly smaller time window for easier identifier control which ultimately is the best guarantee against collisions.

## Why waist one entire null byte ?

Well, as you may have noticed, a null byte (`\0`) is used to distinguish random identifier from user defined identifier. It's pretty clear that some _headache_ could be invested into using fewer space for that purpose. 
Now at the very least and with no identifier, SoUuid still uses 8 bytes of entropy to protect each microseconds, and this _at least_ matches the best _theoretical_ level of guarantees obtained with existing PHP implementations of the RFC.

I say _theoretical_ because this implies that these implementations actually gather microseconds, which does not seems so obvious when you look at the details.

This comes from the fact that [microtime(1)](http://php.net/microtime) is returning a `float` which is indeed limited by the php.ini `precision` directive, defaulting to 14 :
```
; The number of significant digits displayed in floating point numbers.
; http://php.net/precision
precision = 14
```

So by default you get :

```php
microtime(1); // 1517992591.8068
```

While the same micro-time will be available with actual micro-time precision as a string :

```php
microtime(0); // "0.80684200 1517992591"
```

So yes this means that in practice you may find yourself with lower precisions when you use code based on `microtime(1)` to generate UUIDs. With an actual default of a 1000x wider time window to be protected against collisions compared to the RFC.

SoUuid uses a string based approach that actually maintain the microsecond precision :
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

So my current thinking is that, in addition to not being such a XOR myself, this is probably still a good initial stand, because in situations where huge amounts of UUIDs are to be generated, you would need to use identifiers anyway, which was already made easier. And This room could also later be used to extend and version the `SoUuid` format (please be my 1337 ^^).

Besides, you can use `random_bytes(6)` as default identifier if you do not want to bother about identifiers and still want to get the whole 9 bytes of entropy protecting each micro second and lower the collision probability to the pretty insane value of one out of 2^72 = 4 722 366 482 869 645 213 696 for UUIDs generated within the same micro-second.

As [random_compat](https://github.com/paragonie/random_compat) is included as polyfill for [random_bytes()](http://php.net/random_bytes), you don't even have to worry about getting good randomness bellow PHP 7.

## Requirements

SoUuid is tested against php 5.6, 7.0, 7.1, 7.2 and hhvm, but it may run bellow that (might up to 5.3).

## Contributing

Contributions are welcome, do not hesitate to open issues and submit pull requests.

## License

SoUuid is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
