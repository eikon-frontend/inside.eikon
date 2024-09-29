import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { content, backgroundColor, textColor } = attributes;

  const animationDuration = `${content.length / 5}s`;

  return (
    <div {...useBlockProps.save()} style={{ backgroundColor: backgroundColor, color: textColor }} className="wp-block-eikonblocks-marquee">
      <div className="marquee-container">
        <RichText.Content className="content" tagName="p" data-text={content} value={content} style={{ animationDuration }} />
      </div>
    </div>
  );
}
