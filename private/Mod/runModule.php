<?php

namespace Mod;


$ctx = new Context();

$mod_strap = $ctx->init(Path::$config);  
$ctx->di->setShared('mod', $ctx->activeModule);
require $mod_strap;
