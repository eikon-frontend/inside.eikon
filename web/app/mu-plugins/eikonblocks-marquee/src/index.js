import { registerBlockType } from '@wordpress/blocks';

import edit from './edit';
import save from './save';
import metadata from './block.json';
const el = wp.element.createElement;

const icon = el('svg', { width: 24, height: 24 },
  el('path', { d: "m4.6 11 .9-2.2h5l.9 2.2h2.1L8.75 0h-1.5L2.5 11h2.1ZM0 15l3-3v2h13v2H3v2l-3-3Zm9.87-8L8 1.98 6.13 7h3.74Z" })
);

registerBlockType(metadata.name, {
  edit,
  save,
  icon
});
