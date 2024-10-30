import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import './editor.scss';

const Edit = () => {

  return (
    <div {...useBlockProps()}>
      <div className='eikonblock-title'>eikonblock // grid</div>
      <InnerBlocks />
    </div>
  );
};

export default Edit;
