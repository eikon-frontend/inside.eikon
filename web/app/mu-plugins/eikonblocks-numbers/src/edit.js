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
      <div className="ekn-card">
        <div className="ekn-card__main">
          <div className="ekn-field ekn-field--grow">
            <label htmlFor="title" className="ekn-label">{__('Titre', 'eikonblocks')}</label>
            <input
              id="title"
              type="text"
              className="ekn-input"
              value={title}
              onChange={handleTitleChange}
              placeholder={__('Ajouter votre titre', 'eikonblocks')}
            />
          </div>
        </div>
      </div>
      {items.map((item, index) => (
        <div key={index} className="ekn-row">
          <div className="ekn-row__header">
            <span className="ekn-label">{__('Élément', 'eikonblocks')} {index + 1}</span>
            <div className="ekn-row__actions">
              <button
                type="button"
                onClick={() => removeItem(index)}
                className="ekn-btn ekn-btn--action"
                aria-label={__('Supprimer', 'eikonblocks')}
              >
                ✕
              </button>
            </div>
          </div>
          <div className="ekn-row__main">
            <div className="ekn-fields-row">
              <div className="ekn-field ekn-field--grow">
                <label htmlFor={`number-${index}`} className="ekn-label">{__('Nombre', 'eikonblocks')}</label>
                <input
                  id={`number-${index}`}
                  type="number"
                  className="ekn-input"
                  value={item.number}
                  onChange={(event) => handleItemChange(index, 'number', event.target.value)}
                  placeholder={__('Nombre', 'eikonblocks')}
                />
              </div>
              <div className="ekn-field ekn-field--grow">
                <label htmlFor={`text-${index}`} className="ekn-label">{__('Texte', 'eikonblocks')}</label>
                <input
                  id={`text-${index}`}
                  type="text"
                  className="ekn-input"
                  value={item.text}
                  onChange={(event) => handleItemChange(index, 'text', event.target.value)}
                  placeholder={__('Texte', 'eikonblocks')}
                />
              </div>
            </div>
          </div>
        </div>
      ))}
      <button type="button" className="ekn-btn ekn-btn--primary" onClick={addItem}>
        {__('+ Ajouter un élément', 'eikonblocks')}
      </button>
    </div>
  );
}
