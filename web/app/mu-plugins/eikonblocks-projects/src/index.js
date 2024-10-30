import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from './block.json';

const el = wp.element.createElement;
const icon = el('svg', { width: 24, height: 24 },
  el('path', { d: "M9 11H4V5h5v6Zm0 7H4v-6h5v6Zm1 0h5v-6h-5v6Zm11 0h-5v-6h5v6Zm-11-7h5V5h-5v6Zm6 0V5h5v6h-5Z" })
);

registerBlockType(metadata.name, {
  edit: Edit,
  save,
  icon
});
