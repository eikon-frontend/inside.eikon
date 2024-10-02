import { useBlockProps } from '@wordpress/block-editor';
import { colorMap } from './colorUtils';

export default function Save({ attributes }) {
  const { backgroundColor, textColor, selectedYear, selectedSubject } = attributes;

  const bgColorSlug = colorMap[backgroundColor] || '';
  const textColorSlug = colorMap[textColor] || '';

  return (
    <div
      {...useBlockProps.save()}
      className={`mixed-posts bg-${bgColorSlug} text-${textColorSlug}`}
      style={{ backgroundColor: backgroundColor, color: textColor }}
      data-year={selectedYear}
      data-subject={selectedSubject}
    >
    </div>
  );
}


