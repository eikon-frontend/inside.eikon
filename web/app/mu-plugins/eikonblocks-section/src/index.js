import { registerBlockType } from '@wordpress/blocks';

import edit from './edit';
import save from './save';
import metadata from './block.json';
const el = wp.element.createElement;

const icon = el('svg', { width: 24, height: 24 },
  el('path', { d: "M3 11V5h19v6H3Zm0 8h6v-7H3v7Zm7 0h12v-7H10v7Z" })
);

registerBlockType(metadata.name, {
  edit,
  save,
  icon,
  allowedBlocks: metadata.allowedBlocks
});
