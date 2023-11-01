<?php
add_theme_support('post-thumbnails');

foreach (glob(get_template_directory() . "/inc/*.php") as $file) {
  require $file;
}
