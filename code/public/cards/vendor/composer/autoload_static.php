<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitdc43108e5480fdc7339733ec1ceb55d0
{
    public static $prefixLengthsPsr4 = array (
        'F' => 
        array (
            'Firebase\\JWT\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Firebase\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/firebase/php-jwt/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitdc43108e5480fdc7339733ec1ceb55d0::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitdc43108e5480fdc7339733ec1ceb55d0::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitdc43108e5480fdc7339733ec1ceb55d0::$classMap;

        }, null, ClassLoader::class);
    }
}
