<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit635d370427e5e930ebf591449c377029
{
    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit635d370427e5e930ebf591449c377029::$classMap;

        }, null, ClassLoader::class);
    }
}
