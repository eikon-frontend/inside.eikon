<?php

foreach (glob(__DIR__ . '/user-import/*.php') as $file) {
  require_once $file;
}
