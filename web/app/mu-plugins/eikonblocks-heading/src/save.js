import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { content, level, fullWidth } = attributes;

  return (
    <div
      {...useBlockProps.save()}
      className={`wp-block-eikonblocks-heading ${fullWidth ? 'full-width' : ''}`}
    >
      <RichText.Content
        tagName={'h' + level}
        className='content'
        value={content}
      />
    </div>
  );
}
