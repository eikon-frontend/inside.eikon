import { useBlockProps, InspectorControls, PanelColorSettings, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;
  const { items, backgroundColor, textColor } = attributes;

  const handleItemChange = (index, value) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], ...value };
    setAttributes({ items: newItems });
  };

  const handleTitleChange = (index, title) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], title };
    setAttributes({ items: newItems });
  };

  const handleTextChange = (index, text) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], text };
    setAttributes({ items: newItems });
  };

  const addItem = () => {
    setAttributes({ items: [...items, { title: '', text: '' }] });
  };

  return (
    <>
      <InspectorControls>
        <PanelColorSettings
          title={__('Color Settings', 'eikonblocks')}
          initialOpen={true}
          colorSettings={[
            {
              value: backgroundColor,
              onChange: (value) => setAttributes({ backgroundColor: value }),
              label: __('Background Color', 'eikonblocks'),
            },
            {
              value: textColor,
              onChange: (value) => setAttributes({ textColor: value }),
              label: __('Text Color', 'eikonblocks'),
            },
          ]}
        />
      </InspectorControls>
      <div {...useBlockProps()} style={{ backgroundColor: backgroundColor, color: textColor, padding: '20px', borderRadius: '5px' }}>
        <div className='eikonblock-title'>eikonblock // accordion</div>
        {items.map((item, index) => (
          <div key={index} style={{ marginBottom: '20px', padding: '10px', background: 'white', color: 'black', border: '1px solid #ddd', borderRadius: '5px' }}>
            <label style={{ display: 'block', marginBottom: '5px' }}>
              {__('Title', 'eikonblocks')}
              <RichText
                tagName="div"
                value={item.title}
                onChange={(value) => handleTitleChange(index, value)}
                placeholder={__('Enter title', 'eikonblocks')}
                style={{ padding: '8px', marginBottom: '10px', borderRadius: '3px', border: '1px solid #ccc' }}
                allowedFormats={['core/italic']}
              />
            </label>
            <label style={{ display: 'block', marginBottom: '5px' }}>
              {__('Text', 'eikonblocks')}
              <RichText
                tagName="div"
                value={item.text}
                onChange={(value) => handleTextChange(index, value)}
                placeholder={__('Enter text', 'eikonblocks')}
                style={{ padding: '8px', borderRadius: '3px', border: '1px solid #ccc' }}
                allowedFormats={['core/italic', 'core/link']}
              />
            </label>
          </div>
        ))}
        <button
          onClick={addItem}
          style={{
            padding: '10px 20px',
            backgroundColor: '#007cba',
            color: '#fff',
            border: 'none',
            borderRadius: '10px',
            cursor: 'pointer',
          }}
        >
          {__('Add Accordion Item', 'eikonblocks')}
        </button>
      </div>
    </>
  );
}
