import { useBlockProps } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { selectedCPTsData } = attributes;
  const dataCPT = JSON.stringify(selectedCPTsData);

  return (
    <div {...useBlockProps.save()} className="wp-block-eikonblocks-mixed" data-cpt={dataCPT}>
    </div>
  );
}
