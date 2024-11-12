import { registerBlockType } from '@wordpress/blocks';

import edit from './edit';
import save from './save';
import metadata from './block.json';
const el = wp.element.createElement;

const icon = el('svg', { width: 24, height: 24 },
  el('path', {
    d: "M3 5V7H11V5H3ZM11 11H3V9H11V11ZM3 15H11V13H3V15ZM3 19H11V17H3V19ZM21 5H13V19H21V5Z"
  })
);

registerBlockType(metadata.name, {
  edit,
  save,
  icon,
  allowedBlocks: metadata.allowedBlocks
});
