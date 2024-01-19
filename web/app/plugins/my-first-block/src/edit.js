import { useBlockProps, RichText } from '@wordpress/block-editor';
import { TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
  const { title, content } = attributes;

  return (
    <div {...useBlockProps()}>
      <TextControl
        label="Input Label"
        value={title}
        onChange={(value) => setAttributes({ title: value })}
      />
      <RichText
        tagName="p"
        value={content}
        onChange={(content) => setAttributes({ content: content })}
        placeholder={__('Add your custom text', 'my-first-block')}
      />
    </div>
  );
}
