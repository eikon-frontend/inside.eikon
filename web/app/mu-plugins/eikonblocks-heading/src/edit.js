import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import {
  BlockControls,
  RichText,
  useBlockProps
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;

  const { content, level, fullWidth } = attributes;

  return (
    <>
      <BlockControls>
        <ToolbarGroup
          isCollapsed={true}
          icon="heading"
          label={__('Change heading level', 'eikonblocks')}
          controls={['2', '3', '4'].map((index) => ({
            icon: 'heading',
            title: 'H' + index,
            isActive: level === parseInt(index),
            onClick: () => setAttributes({ level: parseInt(index) }),
          }))}
        />
        <ToolbarButton
          icon="align-full-width"
          label={__('Pleine largeur', 'eikonblocks')}
          isPressed={fullWidth}
          onClick={() => setAttributes({ fullWidth: !fullWidth })}
        />
      </BlockControls>
      <div {...useBlockProps()}>
        <div className='eikonblock-title'>eikonblock // titre</div>
        <RichText
          tagName={`h${level}`}
          value={content}
          onChange={(content) => setAttributes({ content })}
          placeholder={__('Write heading…', 'eikonblocks')}
          allowedFormats={['core/italic']}
        />
      </div>
    </>
  );
}
