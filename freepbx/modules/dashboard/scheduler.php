#!/usr/bin/env php
<?php
// vim: set ai ts=4 sw=4 ft=php:
// //	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//
//
// Dashboard Scheduler.
// Runs every minute.
//

// Start quickly.
$bootstrap_settings['freepbx_auth'] = false;  // Just in case.
$restrict_mods = true; // Takes startup from 0.2 seconds to 0.07 seconds.
include '/etc/freepbx.conf';
// My module is called...
$mod = ucfirst(basename(dirname(__FILE__)));
// And now I want to poke it!
$bmo->$mod->runTrigger();
