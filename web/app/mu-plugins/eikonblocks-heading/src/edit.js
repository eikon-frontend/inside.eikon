import { ToolbarGroup } from '@wordpress/components';
import {
  InspectorControls,
  PanelColorSettings,
  BlockControls,
  RichText,
  useBlockProps
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;

  const { content, level, backgroundColor, textColor } = attributes;

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
      </BlockControls>
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
      <div {...useBlockProps()} style={{ backgroundColor: backgroundColor, color: textColor }}>
        <div className='eikonblock-title'>eikonblock // headings</div>
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
