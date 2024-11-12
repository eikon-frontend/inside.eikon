import { registerBlockType } from '@wordpress/blocks';
import { createBlock } from '@wordpress/blocks';

import edit from './edit';
import save from './save';
import metadata from './block.json';
const el = wp.element.createElement;

const icon = el('svg', { width: 24, height: 24 },
  el('path', { d: "M7 3h15c1.1 0 2 .9 2 2v14c0 1.1-.9 2-2 2H7.07c-.69 0-1.3-.36-1.66-.89L0 12l5.41-8.12C5.77 3.35 6.31 3 7 3Zm.5 9c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5-.67-1.5-1.5-1.5-1.5.67-1.5 1.5Zm6.5 1.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5Zm3.5-1.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5-.67-1.5-1.5-1.5-1.5.67-1.5 1.5Z" })
);

registerBlockType(metadata.name, {
  edit,
  save,
  icon,
  allowedBlocks: metadata.allowedBlocks,
  transforms: {
    from: [
      {
        type: 'block',
        blocks: ['core/paragraph'],
        transform: ({ content }) => {
          return createBlock(metadata.name, { content });
        },
      },
    ],
    to: [
      {
        type: 'block',
        blocks: ['core/paragraph'],
        transform: ({ content }) => {
          return createBlock('core/paragraph', { content });
        },
      },
    ],
  },
});
