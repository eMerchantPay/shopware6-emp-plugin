<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit886641c9a2afb9ee03fdc9948105d664
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit886641c9a2afb9ee03fdc9948105d664', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit886641c9a2afb9ee03fdc9948105d664', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit886641c9a2afb9ee03fdc9948105d664::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
