import { useBlockProps } from '@wordpress/block-editor';
import { colorMap } from './colorUtils';

export default function Save({ attributes }) {
  const { backgroundColor, textColor, selectedYear } = attributes;

  const bgColorSlug = colorMap[backgroundColor] || 'white';
  const textColorSlug = colorMap[textColor] || 'blue';

  return (
    <div
      {...useBlockProps.save()}
      className={`mixed-posts bg-${bgColorSlug} text-${textColorSlug}`}
      style={{ backgroundColor: backgroundColor, color: textColor }}
      data-year={selectedYear}
    >
    </div>
  );
}


