import { useBlockProps, RichText, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;
  const { content, backgroundColor, textColor } = attributes;

  return (
    <>
      <InspectorControls>
        <PanelColorSettings
          title={__('Color Settings', 'eikonblocks')}
          initialOpen={true}
          colorSettings={[
            {
              value: backgroundColor,
              onChange: (value) => setAttributes({ backgroundColor: value }),
              label: __('Background Color', 'eikonblocks'),
            },
            {
              value: textColor,
              onChange: (value) => setAttributes({ textColor: value }),
              label: __('Text Color', 'eikonblocks'),
            },
          ]}
        />
      </InspectorControls>
      <div {...useBlockProps()} style={{ backgroundColor: backgroundColor, color: textColor }}>
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
    </>
  );
}
