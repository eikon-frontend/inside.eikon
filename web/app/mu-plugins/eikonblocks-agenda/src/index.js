import { registerBlockType } from '@wordpress/blocks';

import edit from './edit';
import save from './save';
import metadata from './block.json';

const el = wp.element.createElement;

const icon = el('svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
  el('path', { d: 'M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H5V8h14v13zM7 10h5v5H7z' })
);

registerBlockType(metadata.name, {
  edit,
  save,
  icon,
});
