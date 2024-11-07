import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;
  const { title, items } = attributes;

  const handleTitleChange = (event) => {
    setAttributes({ title: event.target.value });
  };

  const handleItemChange = (index, key, value) => {
    const newItems = [...items];
    newItems[index][key] = value;
    setAttributes({ items: newItems });
  };

  const addItem = () => {
    setAttributes({ items: [...items, { number: '', text: '' }] });
  };

  const removeItem = (index) => {
    const newItems = items.filter((_, i) => i !== index);
    setAttributes({ items: newItems });
  };

  return (
    <div {...useBlockProps()}>
      <div className='eikonblock-title'>eikonblock // numbers</div>
      <div style={{ marginBottom: '20px' }}>
        <label htmlFor="title" style={{ display: 'block', marginBottom: '5px' }}>
          {__('Titre', 'eikonblocks')}
        </label>
        <input
          id="title"
          type="text"
          value={title}
          onChange={handleTitleChange}
          placeholder={__('Add your title', 'eikonblocks')}
          style={{ width: '100%', padding: '8px', boxSizing: 'border-box' }}
        />
      </div>
      {items.map((item, index) => (
        <div key={index} style={{ display: 'flex', alignItems: 'flex-end', marginBottom: '10px' }}>
          <div style={{ marginRight: '10px', flex: '1' }}>
            <label htmlFor={`number-${index}`} style={{ display: 'block', marginBottom: '5px' }}>
              {__('Nombre', 'eikonblocks')}
            </label>
            <input
              id={`number-${index}`}
              type="number"
              value={item.number}
              onChange={(event) => handleItemChange(index, 'number', event.target.value)}
              placeholder={__('Number', 'eikonblocks')}
              style={{ width: '100%', padding: '8px', boxSizing: 'border-box' }}
            />
          </div>
          <div style={{ flex: '1' }}>
            <label htmlFor={`text-${index}`} style={{ display: 'block', marginBottom: '5px' }}>
              {__('Texte', 'eikonblocks')}
            </label>
            <input
              id={`text-${index}`}
              type="text"
              value={item.text}
              onChange={(event) => handleItemChange(index, 'text', event.target.value)}
              placeholder={__('Text', 'eikonblocks')}
              style={{ width: '100%', padding: '8px', boxSizing: 'border-box' }}
            />
          </div>
          <button
            onClick={() => removeItem(index)}
            style={{ marginLeft: '10px', padding: '8px', height: '40px' }}
          >
            {__('Remove', 'eikonblocks')}
          </button>
        </div>
      ))}
      <button onClick={addItem}>{__('Add Item', 'eikonblocks')}</button>
    </div>
  );
}
