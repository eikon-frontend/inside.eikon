import { useBlockProps, RichText } from '@wordpress/block-editor';
import { TextControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
  const { title, content, backgroundColor } = attributes;

  return (
    <div {...useBlockProps()} style={{ backgroundColor: backgroundColor }}>
      <SelectControl
        label="Background Color"
        value={backgroundColor}
        options={[
          { label: 'Red', value: 'red' },
          { label: 'Blue', value: 'blue' },
          { label: 'Green', value: 'green' },
        ]}
        onChange={(value) => setAttributes({ backgroundColor: value })}
      />
      <TextControl
        label="Input Label"
        value={title}
        onChange={(value) => setAttributes({ title: value })}
      />
      <RichText
        tagName="p"
        value={content}
        onChange={(content) => setAttributes({ content: content })}
        placeholder={__('Add your custom text', 'eikonblocks')}
      />
    </div>
  );
}
