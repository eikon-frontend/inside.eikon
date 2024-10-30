import { useBlockProps, RichText, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;
  const { content } = attributes;

  return (
    <div {...useBlockProps()}>
      <div className='eikonblock-title'>eikonblock // marquee</div>
      <RichText
        tagName="p"
        value={content}
        onChange={(content) => setAttributes({ content: content })}
        placeholder={__('Add your custom text', 'eikonblocks')}
        allowedFormats={['core/italic']}
        style={{ fontSize: '60px', padding: '0', margin: '0' }}
      />
    </div>
  );
}
