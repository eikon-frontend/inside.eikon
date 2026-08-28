import { useBlockProps, URLInput } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import './editor.scss';

const DEFAULT_LINK_LABEL = 'En savoir plus';

export default function Edit({ attributes, setAttributes }) {
  const { items } = attributes;

  const handleLinkChange = (index, patch) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], link: { ...newItems[index].link, ...patch } };
    setAttributes({ items: newItems });
  };

  const handleFieldChange = (index, key, value) => {
    const newItems = [...items];
    newItems[index] = { ...newItems[index], [key]: value };
    setAttributes({ items: newItems });
  };

  const moveItem = (fromIndex, toIndex) => {
    if (toIndex < 0 || toIndex >= items.length) return;
    const newItems = [...items];
    const element = newItems[fromIndex];
    newItems.splice(fromIndex, 1);
    newItems.splice(toIndex, 0, element);
    setAttributes({ items: newItems });
  };

  const addItem = () => {
    setAttributes({
      items: [...items, { date: '', title: '', link: { url: '', label: '', opensInNewTab: false } }],
    });
  };

  const removeItem = (index) => {
    setAttributes({ items: items.filter((_, i) => i !== index) });
  };

  return (
    <div {...useBlockProps()}>
      <div className="eikonblock-title">eikonblock // agenda</div>

      {items.map((item, index) => {
        const hasLink = !!item.link?.url;
        const isExternal = hasLink && item.link.opensInNewTab;

        return (
          <div key={index} className="ekn-row">
            <div className="ekn-row__header">
              <span className="ekn-label">{__('Événement', 'eikonblocks')} {index + 1}</span>
              <div className="ekn-row__actions">
                <button
                  type="button"
                  className="ekn-btn ekn-btn--action"
                  onClick={() => moveItem(index, index - 1)}
                  disabled={index === 0}
                  aria-label={__('Déplacer l\'événement vers le haut', 'eikonblocks')}
                >
                  ↑
                </button>
                <button
                  type="button"
                  className="ekn-btn ekn-btn--action"
                  onClick={() => moveItem(index, index + 1)}
                  disabled={index === items.length - 1}
                  aria-label={__('Déplacer l\'événement vers le bas', 'eikonblocks')}
                >
                  ↓
                </button>
                <button
                  type="button"
                  className="ekn-btn ekn-btn--action"
                  onClick={() => removeItem(index)}
                  aria-label={__('Supprimer l\'événement', 'eikonblocks')}
                >
                  ✕
                </button>
              </div>
            </div>

            <div className="ekn-row__main">
              <div className="ekn-field">
                <label className="ekn-label">{__('Date', 'eikonblocks')}</label>
                <input
                  type="text"
                  className="ekn-input"
                  value={item.date}
                  onChange={(e) => handleFieldChange(index, 'date', e.target.value)}
                  placeholder={__('ex. 12.06.2026', 'eikonblocks')}
                />
              </div>

              <div className="ekn-field ekn-field--grow">
                <label className="ekn-label">{__('Titre', 'eikonblocks')}</label>
                <input
                  type="text"
                  className="ekn-input"
                  value={item.title}
                  onChange={(e) => handleFieldChange(index, 'title', e.target.value)}
                  placeholder={__('Titre de l\'événement', 'eikonblocks')}
                />
              </div>
            </div>

            <div style={{ padding: '0 16px 16px', borderTop: '1px dashed #e5e7eb', marginTop: '16px' }}>
              <div className="ekn-field" style={{ marginTop: '16px' }}>
                <label className="ekn-label">{__('Lien (optionnel)', 'eikonblocks')}</label>
                <URLInput
                  className="ekn-url-input"
                  value={item.link?.url || ''}
                  onChange={(url) => handleLinkChange(index, { url })}
                  placeholder={__('Coller l\'URL ou rechercher une page…', 'eikonblocks')}
                />
              </div>

              {hasLink && (
                <div style={{ display: 'flex', gap: '16px', marginTop: '12px' }}>
                  <div className="ekn-field ekn-field--grow">
                    <label className="ekn-label">{__('Libellé du bouton', 'eikonblocks')}</label>
                    <input
                      type="text"
                      className="ekn-input"
                      value={item.link?.label || ''}
                      onChange={(e) => handleLinkChange(index, { label: e.target.value })}
                      placeholder={DEFAULT_LINK_LABEL}
                    />
                  </div>

                  <div className="ekn-field" style={{ flexShrink: 0 }}>
                    <label className="ekn-label" style={{ cursor: 'pointer' }}>
                      <input
                        type="checkbox"
                        checked={item.link?.opensInNewTab || false}
                        onChange={(e) => handleLinkChange(index, { opensInNewTab: e.target.checked })}
                      />
                      {__('Nouvel onglet', 'eikonblocks')}
                    </label>
                    <span className={`agenda-editor-icon-badge agenda-editor-icon-badge--${isExternal ? 'external' : 'internal'}`}>
                      {isExternal ? '↗' : '→'}
                    </span>
                  </div>
                </div>
              )}
            </div>
          </div>
        );
      })}

      <button type="button" className="ekn-btn ekn-btn--primary" onClick={addItem}>
        {__('+ Ajouter un événement', 'eikonblocks')}
      </button>
    </div>
  );
}
