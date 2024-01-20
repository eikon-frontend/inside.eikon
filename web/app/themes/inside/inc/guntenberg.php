<?php

add_filter('allowed_block_types_all', 'eikon_allowed_block_types', 25, 2);

function eikon_allowed_block_types($allowed_blocks, $editor_context)
{
  return array(
    'core/image',
    'core/paragraph',
    'core/heading',
    'core/list',
    'core/list-item',
    'create-block/my-first-block'
  );
}
