import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

export default function Save() {
  return (
    <div {...useBlockProps.save()} className={`wp-block-eikonblocks-grid`}>
      <InnerBlocks.Content />
    </div>
  );
};
