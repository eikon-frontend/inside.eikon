import { useBlockProps } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { selectedYear, selectedSection } = attributes;

  return (
    <div {...useBlockProps.save()} className={`wp-block-eikonblocks-projects`} data-year={selectedYear} data-section={selectedSection}></div>
  );
}


