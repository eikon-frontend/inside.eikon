import { registerBlockType } from '@wordpress/blocks';

import edit from './edit';
import save from './save';
import metadata from './block.json';
const el = wp.element.createElement;

const icon = el('svg', { width: 24, height: 24 },
  el('path', {
    d: "M2 5h12c.55 0 1 .45 1 1v12c0 .55 - .45 1 - 1 1H2c - .55 0 - 1 - .45 - 1 - 1V6c0 - .55.45 - 1 1 - 1Zm15 14h2V5h - 2v14Zm4 - 14v14h2V5h - 2ZM8 7.75c1.24 0 2.25 1.01 2.25 2.25S9.24 12.25 8 12.25 5.75 11.24 5.75 10 6.76 7.75 8 7.75Zm - 4.5 8.5V17h9v - .75c0 - 1.5 - 3 - 2.25 - 4.5 - 2.25s - 4.5.75 - 4.5 2.25Z"
  })
);

registerBlockType(metadata.name, {
  edit,
  save,
  icon
});
