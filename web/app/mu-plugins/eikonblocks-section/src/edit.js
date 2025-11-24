import { useBlockProps, InnerBlocks, InspectorControls, BlockControls } from '@wordpress/block-editor';
import { PanelColorSettings } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { SelectControl, PanelBody, ToggleControl, ToolbarGroup, ToolbarButton } from '@wordpress/components';

const Edit = ({ attributes, setAttributes }) => {
  const { backgroundColor, textColor, paddingTop, paddingBottom, isPaddingSymmetrical, isActive } = attributes;

  const paddingClasses = isPaddingSymmetrical
    ? `padding-${paddingTop}`
    : `padding-top-${paddingTop} padding-bottom-${paddingBottom}`;

  return (
    <>
      <BlockControls>
        <ToolbarGroup>
          <ToolbarButton
            icon={isActive ? 'visibility' : 'hidden'}
            label={isActive ? __('Hide', 'eikonblocks') : __('Show', 'eikonblocks')}
            onClick={() => setAttributes({ isActive: !isActive })}
            isPressed={!isActive}
          />
        </ToolbarGroup>
      </BlockControls>
      <InspectorControls>
        <PanelBody title={__('Visibility', 'eikonblocks')} initialOpen={true}>
          <ToggleControl
            label={__('Active', 'eikonblocks')}
            checked={isActive}
            onChange={(value) => setAttributes({ isActive: value })}
          />
        </PanelBody>
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
          <ToggleControl
            label={__('Padding Symmetry', 'eikonblocks')}
            checked={isPaddingSymmetrical}
            onChange={(value) => setAttributes({ isPaddingSymmetrical: value })}
          />
          {isPaddingSymmetrical ? (
            <SelectControl
              label={__('Padding', 'eikonblocks')}
              value={paddingTop}
              options={[
                { label: 'None', value: 'none' },
                { label: 'Small', value: 'small' },
                { label: 'Medium', value: 'medium' },
                { label: 'Big', value: 'big' },
              ]}
              onChange={(size) => setAttributes({ paddingTop: size, paddingBottom: size })}
            />
          ) : (
            <>
              <SelectControl
                label={__('Padding Top', 'eikonblocks')}
                value={paddingTop}
                options={[
                  { label: 'None', value: 'none' },
                  { label: 'Small', value: 'small' },
                  { label: 'Medium', value: 'medium' },
                  { label: 'Big', value: 'big' },
                ]}
                onChange={(size) => setAttributes({ paddingTop: size })}
              />
              <SelectControl
                label={__('Padding Bottom', 'eikonblocks')}
                value={paddingBottom}
                options={[
                  { label: 'None', value: 'none' },
                  { label: 'Small', value: 'small' },
                  { label: 'Medium', value: 'medium' },
                  { label: 'Big', value: 'big' },
                ]}
                onChange={(size) => setAttributes({ paddingBottom: size })}
              />
            </>
          )}
        </PanelBody>
      </InspectorControls>
      <div
        {...useBlockProps({
          className: paddingClasses,
          style: {
            backgroundColor,
            color: textColor,
            opacity: isActive ? 1 : 0.5
          },
        })}
      >
        <div className='eikonblock-title'>eikonblock // section</div>
        <InnerBlocks />
      </div>
    </>
  );
};

export default Edit;
