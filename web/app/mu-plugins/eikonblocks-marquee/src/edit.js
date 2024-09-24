import { useBlockProps, RichText } from '@wordpress/block-editor';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';

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

export default function Edit({ attributes, setAttributes }) {
  const { content, backgroundColor, textColor } = attributes;

  return (
    <div {...useBlockProps()} style={{ backgroundColor: backgroundColor, color: textColor }}>
      <div style={{ display: 'flex', gap: '10px', width: '100%' }}>
        <div style={{ flexGrow: 1 }}>
          <SelectControl
            label="Background Color"
            value={backgroundColor}
            options={colors}
            onChange={(value) => setAttributes({ backgroundColor: value })}
          />
        </div>
        <div style={{ flexGrow: 1 }}>
          <SelectControl
            label="Text Color"
            value={textColor}
            options={colors}
            style={{ width: '100%' }}
            onChange={(value) => setAttributes({ textColor: value })}
          />
        </div>
      </div>
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
