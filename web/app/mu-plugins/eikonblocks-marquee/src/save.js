import { useBlockProps, RichText } from '@wordpress/block-editor';
import { colorMap } from './colorUtils';

export default function Save({ attributes }) {
  const { content, backgroundColor, textColor } = attributes;

  const animationDuration = `${content.length / 5}s`;

  const bgColorSlug = colorMap[backgroundColor] || '';
  const textColorSlug = colorMap[textColor] || '';

  return (
    <div {...useBlockProps.save()} className={`wp-block-eikonblocks-marquee bg-${bgColorSlug} text-${textColorSlug}`}>
      <div className="marquee-container">
        <RichText.Content className="content" tagName="p" data-text={content} value={content} style={{ animationDuration }} />
      </div>
    </div>
  );
}
