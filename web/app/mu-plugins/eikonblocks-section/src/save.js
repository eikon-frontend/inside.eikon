import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { colorMap } from './colorUtils';

export default function Save({ attributes }) {
  const { backgroundColor, textColor, paddingTop, paddingBottom, isPaddingSymmetrical } = attributes;

  const bgColorSlug = colorMap[backgroundColor] || backgroundColor;
  const textColorSlug = colorMap[textColor] || textColor;

  const paddingClasses = isPaddingSymmetrical
    ? `padding-top-${paddingTop} padding-bottom-${paddingTop}`
    : `padding-top-${paddingTop} padding-bottom-${paddingBottom}`;

  const className = [
    `wp-block-eikonblocks-section`,
    `bg-${bgColorSlug}`,
    `text-${textColorSlug}`,
    paddingClasses
  ].join(' ');

  return (
    <div
      {...useBlockProps.save({
        className: className,
      })}
    >
      <InnerBlocks.Content />
    </div>
  );
};
