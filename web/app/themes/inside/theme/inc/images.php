<?php

add_image_size( 'medium', 400 );


function getAspectRatio( $width, $height ) {
  $greatestCommonDivisor = static function($width, $height) use (&$greatestCommonDivisor) {
      return ($width % $height) ? $greatestCommonDivisor($height, $width % $height) : $height;
  };

  $divisor = $greatestCommonDivisor($width, $height);

  return $width / $divisor . '/' . $height / $divisor;
}
