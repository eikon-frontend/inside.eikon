<?php

foreach (glob(__DIR__ . '/cpt/*.php') as $file) {
  require_once $file;
}
