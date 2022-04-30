<?php

declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPUnit\Runner;

use function array_slice;
use function explode;
use function implode;
use function strpos;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
final class Version
{
    /**
     * @var string
     */
    private static $pharVersion = '';

    /**
     * @var string
     */
    private static $version = '';

    /**
     * Returns the current version of PHPUnit.
     */
    public static function id(): string
    {
        if (self::$pharVersion !== '') {
            return self::$pharVersion;
        }

        if (self::$version === '') {
            $file = __DIR__ . '/../../vendor/phpunit/phpunit/src/Runner/Version.php';
            preg_match('/\d+.\d+.\d+/', file_get_contents($file), $match);
            self::$version = $match[0];
        }

        return self::$version;
    }

    public static function series(): string
    {
        if (strpos(self::id(), '-')) {
            $version = explode('-', self::id())[0];
        } else {
            $version = self::id();
        }

        return implode('.', array_slice(explode('.', $version), 0, 2));
    }

    public static function getVersionString(): string
    {
        return 'PHPUnit ' . self::id();
    }
}
