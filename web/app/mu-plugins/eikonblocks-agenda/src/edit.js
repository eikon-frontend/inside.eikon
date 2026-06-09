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
          <div key={index} className="agenda-editor-row">
            <div className="agenda-editor-row__header">
              <span className="agenda-editor-row__counter">{__('Event', 'eikonblocks')} {index + 1}</span>
              <button
                className="agenda-editor-row__remove"
                onClick={() => removeItem(index)}
                aria-label={__('Remove event', 'eikonblocks')}
              >
                ✕
              </button>
            </div>

            <div className="agenda-editor-row__main">
              <div className="agenda-editor-field">
                <label className="agenda-editor-label">{__('Date', 'eikonblocks')}</label>
                <input
                  type="text"
                  className="agenda-editor-input"
                  value={item.date}
                  onChange={(e) => handleFieldChange(index, 'date', e.target.value)}
                  placeholder={__('e.g. 12.06.2026', 'eikonblocks')}
                />
              </div>

              <div className="agenda-editor-field agenda-editor-field--grow">
                <label className="agenda-editor-label">{__('Title', 'eikonblocks')}</label>
                <input
                  type="text"
                  className="agenda-editor-input"
                  value={item.title}
                  onChange={(e) => handleFieldChange(index, 'title', e.target.value)}
                  placeholder={__('Event title', 'eikonblocks')}
                />
              </div>
            </div>

            <div className="agenda-editor-row__link">
              <div className="agenda-editor-field">
                <label className="agenda-editor-label">{__('Link (optional)', 'eikonblocks')}</label>
                <URLInput
                  className="agenda-editor-urlinput"
                  value={item.link?.url || ''}
                  onChange={(url) => handleLinkChange(index, { url })}
                  placeholder={__('Paste URL or search for a page…', 'eikonblocks')}
                />
              </div>

              {hasLink && (
                <div className="agenda-editor-row__link-options">
                  <div className="agenda-editor-field agenda-editor-field--grow">
                    <label className="agenda-editor-label">{__('Button label', 'eikonblocks')}</label>
                    <input
                      type="text"
                      className="agenda-editor-input"
                      value={item.link?.label || ''}
                      onChange={(e) => handleLinkChange(index, { label: e.target.value })}
                      placeholder={DEFAULT_LINK_LABEL}
                    />
                  </div>

                  <div className="agenda-editor-field agenda-editor-field--newtab">
                    <label className="agenda-editor-label agenda-editor-label--checkbox">
                      <input
                        type="checkbox"
                        checked={item.link?.opensInNewTab || false}
                        onChange={(e) => handleLinkChange(index, { opensInNewTab: e.target.checked })}
                      />
                      {__('New tab', 'eikonblocks')}
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

      <button className="agenda-editor-add" onClick={addItem}>
        {__('+ Add event', 'eikonblocks')}
      </button>
    </div>
  );
}
