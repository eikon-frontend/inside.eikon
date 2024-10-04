
import { registerBlockType } from '@wordpress/blocks';

import edit from './edit';
import save from './save';
import metadata from './block.json';

const el = wp.element.createElement;

const icon = el('svg', { width: 24, height: 24 },
  el('path', { d: "M5 3h14c1.1 0 2 .9 2 2v14c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V5c0-1.1.9-2 2-2Zm7 14h2V7h-4v2h2v8Z" })
);

registerBlockType(metadata.name, {
  edit,
  save,
  icon,
});
