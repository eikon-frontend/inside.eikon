import { useBlockProps, BlockControls, AlignmentToolbar, URLInput } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;
  const { items, alignment } = attributes;

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

  const handleStyleChange = (index, style) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], style };
    setAttributes({ items: newItems });
  };

  const handleIconChange = (index, icon) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], icon };
    setAttributes({ items: newItems });
  };

  const handleUrlChange = (index, link) => {
    const newItems = [...items];
    newItems[index] = {
      ...newItems[index],
      url: link.url || link,
      opensInNewTab: link.opensInNewTab !== undefined ? link.opensInNewTab : newItems[index].opensInNewTab
    };
    setAttributes({ items: newItems });
  };

  const handleOpenInNewTabChange = (index, opensInNewTab) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], opensInNewTab };
    setAttributes({ items: newItems });
  };

  const addItem = () => {
    setAttributes({ items: [...items, { url: '', opensInNewTab: false, title: '', style: 'plain', icon: 'arrow' }] });
  };

  const removeItem = (index) => {
    const newItems = [...items];
    newItems.splice(index, 1);
    setAttributes({ items: newItems });
  };

  return (
    <>
      <BlockControls>
        <AlignmentToolbar
          value={alignment}
          onChange={(newAlignment) => setAttributes({ alignment: newAlignment || 'left' })}
        />
      </BlockControls>
      <div {...useBlockProps()}>
        <div className='eikonblock-title'>eikonblock // buttons</div>
        {items.map((item, index) => (
          <div key={index} className="ekn-row">
            <div className="ekn-row__header">
              <span className="ekn-label">{__('Bouton', 'eikonblocks')} {index + 1}</span>
              <div className="ekn-row__actions">
                <button
                  type="button"
                  onClick={() => removeItem(index)}
                  className="ekn-btn ekn-btn--action"
                  aria-label={__('Supprimer le bouton', 'eikonblocks')}
                >
                  ✕
                </button>
              </div>
            </div>
            <div className="ekn-row__main">
              <div className="ekn-fields-row">
                <div className="ekn-field ekn-field--grow">
                  <label className="ekn-label">{__('Label', 'eikonblocks')}</label>
                  <input
                    type="text"
                    value={item.title || ''}
                    onChange={(e) => handleTitleChange(index, e.target.value)}
                    placeholder={__('Saisir le titre', 'eikonblocks')}
                    className="ekn-input"
                  />
                </div>
                <div className="ekn-field ekn-field--grow">
                  <label className="ekn-label">{__('Lien', 'eikonblocks')}</label>
                  <URLInput
                    value={item.url || ''}
                    onChange={(url, link) => handleUrlChange(index, link || { url })}
                    placeholder={__('Saisir l\'URL', 'eikonblocks')}
                    className="ekn-url-input"
                  />
                </div>
              </div>
              <div className="ekn-fields-row">
                <div className="ekn-field ekn-field--grow">
                  <label className="ekn-label">{__('Style', 'eikonblocks')}</label>
                  <select
                    value={item.style}
                    onChange={(e) => handleStyleChange(index, e.target.value)}
                    className="ekn-select"
                  >
                    <option value="plain">{__('Plein', 'eikonblocks')}</option>
                    <option value="outline">{__('Contour', 'eikonblocks')}</option>
                  </select>
                </div>
                <div className="ekn-field ekn-field--grow">
                  <label className="ekn-label">{__('Icône', 'eikonblocks')}</label>
                  <select
                    value={item.icon}
                    onChange={(e) => handleIconChange(index, e.target.value)}
                    className="ekn-select"
                  >
                    <option value="none">{__('Aucune', 'eikonblocks')}</option>
                    <option value="arrow">{__('Flèche', 'eikonblocks')}</option>
                    <option value="download">{__('Téléchargement', 'eikonblocks')}</option>
                    <option value="external">{__('Externe', 'eikonblocks')}</option>
                  </select>
                </div>
                <div className="ekn-field" style={{ minHeight: '38px', justifyContent: 'flex-end', paddingBottom: '10px' }}>
                  <label className="ekn-checkbox">
                    <input
                      type="checkbox"
                      checked={item.opensInNewTab}
                      onChange={(e) => handleOpenInNewTabChange(index, e.target.checked)}
                    />
                    {__('Ouvrir dans un nouvel onglet', 'eikonblocks')}
                  </label>
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
          {__('Ajouter un bouton', 'eikonblocks')}
        </button>
      </div>
    </>
  );
}
