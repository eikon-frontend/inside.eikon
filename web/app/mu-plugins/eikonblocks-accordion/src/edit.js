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
          <div key={index} className="ekn-row">
            <div className="ekn-row__header">
              <span className="ekn-label">{__('Élément', 'eikonblocks')} {index + 1}</span>
              <div className="ekn-row__actions">
                <button
                  type="button"
                  onClick={() => handleRemoveItem(index)}
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
                  <label className="ekn-label">{__('Titre', 'eikonblocks')}</label>
                  <RichText
                    tagName="div"
                    value={item.title}
                    onChange={(value) => handleTitleChange(index, value)}
                    placeholder={__('Saisir le titre', 'eikonblocks')}
                    className="ekn-input"
                    allowedFormats={['core/italic']}
                  />
                </div>
              </div>
              <div className="ekn-fields-row">
                <div className="ekn-field ekn-field--grow">
                  <label className="ekn-label">{__('Texte', 'eikonblocks')}</label>
                  <RichText
                    tagName="div"
                    value={item.text}
                    onChange={(value) => handleTextChange(index, value)}
                    placeholder={__('Saisir le texte', 'eikonblocks')}
                    className="ekn-input"
                    allowedFormats={['core/italic', 'core/link']}
                  />
                </div>
              </div>
            </div>
          </div>
        ))}
        <button
          type="button"
          onClick={addItem}
          className="ekn-btn ekn-btn--primary"
        >
          {__('+ Ajouter un élément', 'eikonblocks')}
        </button>
      </div>
    </>
  );
}
