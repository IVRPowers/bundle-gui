<?php

if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
sql('DROP TABLE IF EXISTS vxml');
sql('DROP TABLE IF EXISTS vxmllicense');
sql('DROP TABLE IF EXISTS vxmlconfiguration');
sql('DROP TABLE IF EXISTS tts');
sql('DROP TABLE IF EXISTS mrcp');