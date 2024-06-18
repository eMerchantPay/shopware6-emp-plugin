<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit123d98924914628a491e967ed4069c3a
{
    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'Genesis\\' => 8,
        ),
        'E' => 
        array (
            'Emerchantpay\\Genesis\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Genesis\\' => 
        array (
            0 => __DIR__ . '/..' . '/genesisgateway/genesis_php/src/Genesis',
        ),
        'Emerchantpay\\Genesis\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit123d98924914628a491e967ed4069c3a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit123d98924914628a491e967ed4069c3a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit123d98924914628a491e967ed4069c3a::$classMap;

        }, null, ClassLoader::class);
    }
}
