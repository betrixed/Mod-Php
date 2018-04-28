# Mod-Php
This is a re-working, and modularisation of the https://github.com/betrixed/PCan website PHP framework.

It requires Phalcon 3.3, Php 7.2.
It requires Toml-Pun8 extensions, (https://github.com/betrixed/Toml-Pun8/blob/master/README.md) 
compiled using PHP-CPP.

Modularisation avoids the monolithic and controller inheritance dependency of PCan. Conversion process is still underway. Most database and site design is kept exactly the same, while it untangles some file dependency.

Reasons for TOML configuration -  I disliked writing code for Phalcon Router configurations.

Plan - module based router configuration files in TOML format.  Fast TOML-Reader (PHP-CPP compiled) to a Pun\KeyTable (Red-Black tree in C++, string indexed).  RouteUnpack constructs Router configuration. Module Router objects are serialized to a cache, which is updated when router configuration TOML source files are updated.

Included is a module for an interface to the Phalcon devtools web interface for Database Migrations.

