<?php

use QRcode\QRcode;
use QRcode\QRstr;

add_theme_support('post-thumbnails');

foreach (glob(get_template_directory() . "/inc/*.php") as $file) {
  require $file;
}
