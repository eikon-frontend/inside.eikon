import { useBlockProps } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { selectedYear } = attributes;


  return (
    <div {...useBlockProps.save()} className={`mixed-posts`} data-year={selectedYear}></div>
  );
}


