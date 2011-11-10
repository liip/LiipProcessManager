<?php

spl_autoload_register(function($class) {
    $class = ltrim($class, '\\');
    if (0 === strpos($class, 'Liip\ProcessManager\\')) {
        $file = __DIR__.'/../'.str_replace('\\', '/', substr($class, strlen('Liip\ProcessManager\\'))).'.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});
