import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelColorSettings } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const Edit = ({ attributes, setAttributes }) => {
  const { backgroundColor, textColor } = attributes;

  return (
    <>
      <InspectorControls>
        <PanelColorSettings
          title={__('Color Settings', 'eikonblocks')}
          initialOpen={true}
          colorSettings={[
            {
              value: backgroundColor,
              onChange: (color) => setAttributes({ backgroundColor: color }),
              label: __('Background Color', 'eikonblocks'),
            },
            {
              value: textColor,
              onChange: (color) => setAttributes({ textColor: color }),
              label: __('Text Color', 'eikonblocks'),
            },
          ]}
        />
      </InspectorControls>
      <div {...useBlockProps()} style={{ backgroundColor, color: textColor }}>
        <div className='eikonblock-title'>eikonblock // section</div>
        <InnerBlocks />
      </div>
    </>
  );
};

export default Edit;
