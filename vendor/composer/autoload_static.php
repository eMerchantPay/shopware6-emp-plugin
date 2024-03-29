<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInited7a6cd59e81def599d7d0beb72a801d
{
    public static $prefixLengthsPsr4 = array (
        'E' => 
        array (
            'Emerchantpay\\Genesis\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Emerchantpay\\Genesis\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'G' => 
        array (
            'Genesis' => 
            array (
                0 => __DIR__ . '/..' . '/genesisgateway/genesis_php/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInited7a6cd59e81def599d7d0beb72a801d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInited7a6cd59e81def599d7d0beb72a801d::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInited7a6cd59e81def599d7d0beb72a801d::$prefixesPsr0;
            $loader->classMap = ComposerStaticInited7a6cd59e81def599d7d0beb72a801d::$classMap;

        }, null, ClassLoader::class);
    }
}
