import { useBlockProps } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { selectedYears = [], selectedSections = [] } = attributes;

  return (
    <div
      {...useBlockProps.save()}
      className="wp-block-eikonblocks-projects"
      data-year={selectedYears.join(',')}
      data-section={selectedSections.join(',')}
    ></div>
  );
}


