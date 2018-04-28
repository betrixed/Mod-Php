<?php

/** No namespace defined allows inclusion into any namespace, like a trait */
use Phalcon\Bootstrap;

include 'webtools.config.php';
require PTOOLSPATH . '/bootstrap/autoload.php';

$bootstrap = new \Phalcon\Web\Mod\Bootstrap(
        $ctx->getDI(), [
    'ptools_path' => PTOOLSPATH,
    'ptools_ip' => PTOOLS_IP,
    'base_path' => BASE_PATH,
]);

if (APPLICATION_ENV === ENV_TESTING) {
    return $bootstrap->run();
} else {
    echo $bootstrap->run();
}

