import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;
  const { content, backgroundColor, textColor } = attributes;

  const colors = [
    { label: 'Blue', value: 'blue' },
    { label: 'Black', value: 'black' },
    { label: 'White', value: 'white' },
    { label: 'Red', value: 'red' },
    { label: 'Orange', value: 'orange' },
    { label: 'Fuchsia', value: 'fuchsia' },
    { label: 'Pink', value: 'pink' },
    { label: 'Violet', value: 'violet' },
  ]

  return (
    <>
      <InspectorControls>
        <PanelBody title="Color Settings">
          <SelectControl
            label="Background Color"
            value={backgroundColor}
            options={colors}
            onChange={(value) => setAttributes({ backgroundColor: value })}
          />
          <SelectControl
            label="Text Color"
            value={textColor}
            options={colors}
            style={{ width: '100%' }}
            onChange={(value) => setAttributes({ textColor: value })}
          />
        </PanelBody>
      </InspectorControls>
      <div {...useBlockProps()} style={{ backgroundColor: backgroundColor, color: textColor }}>
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
