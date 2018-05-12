<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit47a1004a35907ed07dad2ae55f759a04
{
    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'LLMS\\' => 5,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'LLMS\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit47a1004a35907ed07dad2ae55f759a04::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit47a1004a35907ed07dad2ae55f759a04::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}