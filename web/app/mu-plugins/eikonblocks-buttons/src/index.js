
import { registerBlockType } from '@wordpress/blocks';


import edit from './edit';
import save from './save';
import metadata from './block.json';
const el = wp.element.createElement;

const iconEl = el('svg', { width: 24, height: 24 },
  el('path', { d: "M3 3h18c1.1 0 2 .9 2 2v14c0 1.1-.9 2-2 2H3c-1.1 0-2-.9-2-2V5c0-1.1.9-2 2-2Zm0 16h18v-3H3v3Z" })
);

registerBlockType(metadata.name, {
  edit,
  save,
  icon: iconEl,
});
