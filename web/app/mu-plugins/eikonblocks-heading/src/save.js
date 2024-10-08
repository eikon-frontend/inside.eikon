import { useBlockProps, RichText } from '@wordpress/block-editor';
import { colorMap } from './colorUtils';

export default function Save({ attributes }) {
  const { content, level, backgroundColor, textColor } = attributes;

  const bgColorSlug = colorMap[backgroundColor] || textColor;
  const textColorSlug = colorMap[textColor] || backgroundColor;

  return (
    <div {...useBlockProps.save()} className={`wp-block-eikonblocks-heading bg-${bgColorSlug} text-${textColorSlug}`}>
      <RichText.Content
        tagName={'h' + level}
        className='content'
        value={content}
      />
    </div>
  );
}
