import { registerBlockType } from '@wordpress/blocks';

import edit from './edit';
import save from './save';
import metadata from './block.json';
const el = wp.element.createElement;

const icon = el('svg', { width: 24, height: 24 },
  el('path', { d: "M3 3V21H21V3H3ZM11 19H5V13H11V19ZM5 11H11V5H5V11ZM19 19H13V13H19V19ZM13 11H19V5H13V11Z" })
);

registerBlockType(metadata.name, {
  edit,
  save,
  icon
});
