import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from './block.json';

const el = wp.element.createElement;
const icon = el('svg', { width: 24, height: 24 },
  el('path', { d: "M11 13H3V3h8v10Zm0 8H3v-6h8v6Zm2 0h8V11h-8v10Zm0-12V3h8v6h-8Z" })
);

registerBlockType(metadata.name, {
  edit: Edit,
  save,
});
