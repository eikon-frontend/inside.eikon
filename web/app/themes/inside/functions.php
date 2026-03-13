<?php

// Temporary debug: log which block triggers the namespace error
add_action('init', function () {
  add_filter('doing_it_wrong_trigger_error', function ($trigger, $function_name, $message) {
    if ($function_name === 'WP_Block_Type_Registry::register') {
      error_log('BLOCK NAMESPACE DEBUG: ' . $message);
      error_log('BLOCK NAMESPACE TRACE: ' . (new \Exception())->getTraceAsString());
    }
    return $trigger;
  }, 10, 3);
}, 0);

foreach (glob(get_template_directory() . "/inc/*.php") as $file) {
  require $file;
}
