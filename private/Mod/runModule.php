<?php

namespace Mod;


$ctx = new Context();

$ctx->init(Path::$config);  

if (defined('MODULE')) {
    $ctx->di->setShared('mod', $ctx->activeModule);
    require MODULE;
}
else {
    echo "No MODULE  is defined";
}