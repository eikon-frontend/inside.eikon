import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { colorMap } from './colorUtils';

export default function Save({ attributes }) {
  const { backgroundColor, textColor, paddingSize } = attributes; // Include paddingSize

  const bgColorSlug = colorMap[backgroundColor] || backgroundColor;
  const textColorSlug = colorMap[textColor] || textColor;

  return (
    <div
      {...useBlockProps.save({
        className: `wp-block-eikonblocks-section bg-${bgColorSlug} text-${textColorSlug} padding-${paddingSize}`, // Add padding class
      })}
    >
      <InnerBlocks.Content />
    </div>
  );
};
