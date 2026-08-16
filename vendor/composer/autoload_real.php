<?php
// Minimal Composer-compatible autoloader for committed vendor/ (PHPMailer).
class ComposerAutoloaderInitSunview
{
    public static function getLoader()
    {
        require __DIR__ . '/ClassLoader.php';
        $loader = new \Composer\Autoload\ClassLoader();
        $map = require __DIR__ . '/autoload_psr4.php';
        foreach ($map as $namespace => $path) {
            $loader->setPsr4($namespace, $path);
        }
        $loader->register(true);
        return $loader;
    }
}