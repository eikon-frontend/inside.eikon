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
        <div key={index} style={{ display: 'flex', alignItems: 'center', marginBottom: '10px' }}>
          <div style={{ marginRight: '10px' }}>
            <label htmlFor={`number-${index}`} style={{ display: 'block', marginBottom: '5px' }}>
              {__('Nombre', 'eikonblocks')}
            </label>
            <input
              id={`number-${index}`}
              type="number"
              value={item.number}
              onChange={(event) => handleItemChange(index, 'number', event.target.value)}
              placeholder={__('Number', 'eikonblocks')}
              style={{ padding: '8px', boxSizing: 'border-box' }}
            />
          </div>
          <div style={{ flexGrow: '1' }}>
            <label htmlFor={`text-${index}`} style={{ display: 'block', marginBottom: '5px' }}>
              {__('Texte', 'eikonblocks')}
            </label>
            <input
              id={`text-${index}`}
              type="text"
              value={item.text}
              onChange={(event) => handleItemChange(index, 'text', event.target.value)}
              placeholder={__('Text', 'eikonblocks')}
              style={{ padding: '8px', boxSizing: 'border-box' }}
            />
          </div>
        </div>
      ))}
      <button onClick={addItem}>{__('Add Item', 'eikonblocks')}</button>
    </div>
  );
}
