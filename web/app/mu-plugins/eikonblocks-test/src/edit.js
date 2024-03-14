import { useState } from '@wordpress/element';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { TextControl, SelectControl } from '@wordpress/components';
import { URLInput } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
  const { title, content, backgroundColor } = attributes;
  const [url, setUrl] = useState('');

  const handleUrlChange = (url) => {
    setUrl(url);
    setAttributes({ url: url });
  };

  return (
    <div {...useBlockProps()} style={{ backgroundColor: backgroundColor }}>

      <SelectControl
        label="Background Color"
        value={backgroundColor}
        options={[
          { label: 'Red', value: '#fee' },
          { label: 'Blue', value: '#eef' },
          { label: 'Green', value: '#efe' },
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
      <URLInput
        value={attributes.url}
        onChange={handleUrlChange}
        className='url'
      />
    </div>
  );
}
