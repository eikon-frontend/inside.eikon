import { useBlockProps } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  // This block uses PHP rendering, so we don't need to save anything here
  // The PHP render_callback will handle the output
  return (
    <div {...useBlockProps.save()} className="wp-block-eikonblocks-newslist">
    </div>
  );
}
