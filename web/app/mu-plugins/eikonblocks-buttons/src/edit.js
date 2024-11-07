import { useBlockProps, __experimentalLinkControl as LinkControl, BlockControls, AlignmentToolbar } from '@wordpress/block-editor';
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
          <div key={index} style={{ marginBottom: '5px', padding: '10px', background: 'white', color: 'black', border: '1px solid #ddd', borderRadius: '5px' }}>
            <table style={{ width: '100%' }}>
              <tbody>
                <tr>
                  <td style={{ width: '30%', verticalAlign: 'top', paddingRight: '10px', paddingTop: '6px' }}>
                    <label>{__('Label', 'eikonblocks')}</label>
                  </td>
                  <td>
                    <input
                      type="text"
                      value={item.title || ''}
                      onChange={(e) => handleTitleChange(index, e.target.value)}
                      placeholder={__('Enter title', 'eikonblocks')}
                      style={{ width: '100%', padding: '8px', borderRadius: '3px', border: '1px solid #ccc' }}
                    />
                  </td>
                </tr>
                <tr>
                  <td style={{ verticalAlign: 'top', paddingRight: '10px', paddingTop: '6px' }}>
                    <label>{__('Lien', 'eikonblocks')}</label>
                  </td>
                  <td>
                    <LinkControl
                      value={item}
                      onChange={(value) => handleItemChange(index, value)}
                      settings={[
                        {
                          id: 'opensInNewTab',
                          title: __('Open in new tab', 'eikonblocks'),
                        },
                      ]}
                      style={{ width: '100%', padding: '0' }}
                    />
                  </td>
                </tr>
                <tr>
                  <td style={{ verticalAlign: 'top', paddingRight: '10px', paddingTop: '6px' }}>
                    <label>{__('Style', 'eikonblocks')}</label>
                  </td>
                  <td>
                    <select
                      value={item.style}
                      onChange={(e) => handleStyleChange(index, e.target.value)}
                      style={{ width: '100%', padding: '8px', borderRadius: '3px', border: '1px solid #ccc' }}
                    >
                      <option value="plain">{__('Plain', 'eikonblocks')}</option>
                      <option value="outline">{__('Outline', 'eikonblocks')}</option>
                    </select>
                  </td>
                </tr>
                <tr>
                  <td style={{ verticalAlign: 'top', paddingRight: '10px', paddingTop: '6px' }}>
                    <label>{__('Icon', 'eikonblocks')}</label>
                  </td>
                  <td>
                    <select
                      value={item.icon}
                      onChange={(e) => handleIconChange(index, e.target.value)}
                      style={{ width: '100%', padding: '8px', borderRadius: '3px', border: '1px solid #ccc' }}
                    >
                      <option value="none">{__('None', 'eikonblocks')}</option>
                      <option value="arrow">{__('Arrow', 'eikonblocks')}</option>
                      <option value="download">{__('Download', 'eikonblocks')}</option>
                      <option value="external">{__('External', 'eikonblocks')}</option>
                    </select>
                  </td>
                </tr>
              </tbody>
            </table>
            <button
              onClick={() => removeItem(index)}
              style={{
                marginTop: '10px',
                padding: '8px 16px',
                backgroundColor: '#d63638',
                color: '#fff',
                border: 'none',
                borderRadius: '5px',
                cursor: 'pointer',
              }}
            >
              {__('Supprimer le bouton', 'eikonblocks')}
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
          {__('Ajouter un bouton', 'eikonblocks')}
        </button>
      </div>
    </>
  );
}
