<?php

/*
Author:             Pushnews <developers@pushnews.eu>
License:            GPLv2 or later
*/

header("Service-Worker-Allowed: /");
header("Content-Type: application/javascript");
header("X-Robots-Tag: none");

echo "importScripts('https://cdn.pn.vg/push/pushnews-sw.js');";
