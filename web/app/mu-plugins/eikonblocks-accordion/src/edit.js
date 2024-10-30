import { useBlockProps, InspectorControls, PanelColorSettings, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;
  const { items } = attributes;

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

  const handleRemoveItem = (index) => {
    const newItems = items.filter((_, i) => i !== index);
    setAttributes({ items: newItems });
  };

  return (
    <>
      <div {...useBlockProps()}>
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
            <button
              onClick={() => handleRemoveItem(index)}
              style={{
                marginTop: '10px',
                padding: '8px 16px',
                backgroundColor: '#d9534f',
                color: '#fff',
                border: 'none',
                borderRadius: '5px',
                cursor: 'pointer',
              }}
            >
              {__('Supprimer', 'eikonblocks')}
            </button>
          </div>
        ))}
        <button
          onClick={addItem}
          style={{
            marginTop: '10px',
            padding: '8px 16px',
            backgroundColor: '#333',
            color: '#fff',
            border: 'none',
            borderRadius: '5px',
            cursor: 'pointer',
          }}
        >
          {__('Ajouter un élément', 'eikonblocks')}
        </button>
      </div>
    </>
  );
}
