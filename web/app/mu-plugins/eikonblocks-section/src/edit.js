import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelColorSettings } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { SelectControl, PanelBody } from '@wordpress/components'; // Update imports

const Edit = ({ attributes, setAttributes }) => {
  const { backgroundColor, textColor, paddingSize } = attributes;

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
        <PanelBody title={__('Padding Settings', 'eikonblocks')} initialOpen={true}>
          <SelectControl
            label={__('Padding Size', 'eikonblocks')}
            value={paddingSize}
            options={[
              { label: 'None', value: 'none' },
              { label: 'Small', value: 'small' },
              { label: 'Medium', value: 'medium' },
              { label: 'Big', value: 'big' },
            ]}
            onChange={(size) => setAttributes({ paddingSize: size })}
          />
        </PanelBody>
      </InspectorControls>
      <div
        {...useBlockProps({
          className: `padding-${paddingSize}`, // Apply the class
          style: { backgroundColor, color: textColor },
        })}
      >
        <div className='eikonblock-title'>eikonblock // section</div>
        <InnerBlocks />
      </div>
    </>
  );
};

export default Edit;
