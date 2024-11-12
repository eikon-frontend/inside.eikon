import { registerBlockType } from '@wordpress/blocks';
import { createBlock } from '@wordpress/blocks';

import edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockType(metadata.name, {
  edit,
  save,
  allowedBlocks: metadata.allowedBlocks,
  transforms: {
    from: [
      {
        type: 'block',
        blocks: ['core/paragraph'],
        transform: ({ content }) => {
          return createBlock(metadata.name, {
            content,
          });
        },
      },
    ],
    to: [
      {
        type: 'block',
        blocks: ['core/paragraph'],
        transform: ({ content }) => {
          return createBlock('core/paragraph', {
            content,
          });
        },
      },
    ],
  },
});
