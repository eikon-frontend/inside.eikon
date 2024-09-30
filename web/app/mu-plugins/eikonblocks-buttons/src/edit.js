import { useBlockProps, InspectorControls, PanelColorSettings, __experimentalLinkControl as LinkControl } from '@wordpress/block-editor';
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

  const addItem = () => {
    setAttributes({ items: [...items, { url: '', opensInNewTab: false, title: '' }] });
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
        {items.map((item, index) => (
          <div key={index} style={{ marginBottom: '20px', padding: '10px', background: 'white', color: 'black', border: '1px solid #ddd', borderRadius: '5px' }}>
            <label style={{ display: 'block', marginBottom: '5px' }}>
              {__('Titre du bouton', 'eikonblocks')}
              <input
                type="text"
                value={item.title || ''}
                onChange={(e) => handleTitleChange(index, e.target.value)}
                placeholder={__('Enter title', 'eikonblocks')}
                style={{ width: '100%', padding: '8px', marginBottom: '10px', borderRadius: '3px', border: '1px solid #ccc' }}
              />
            </label>
            <label style={{ display: 'block', marginBottom: '5px' }}>
              {__('Lien', 'eikonblocks')}
              <LinkControl
                value={item}
                onChange={(value) => handleItemChange(index, value)}
                settings={[
                  {
                    id: 'opensInNewTab',
                    title: __('Open in new tab', 'eikonblocks'),
                  },
                ]}
                style={{ width: '100%' }}
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
          {__('Ajouter un bouton', 'eikonblocks')}
        </button>
      </div>
    </>
  );
}
