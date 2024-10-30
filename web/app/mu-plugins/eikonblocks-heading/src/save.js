import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { content, level } = attributes;

  return (
    <div {...useBlockProps.save()} className="wp-block-eikonblocks-heading">
      <RichText.Content
        tagName={'h' + level}
        className='content'
        value={content}
      />
    </div>
  );
}
