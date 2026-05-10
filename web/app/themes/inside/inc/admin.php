<?php

foreach (glob(__DIR__ . '/admin/*.php') as $file) {
  require_once $file;
}
