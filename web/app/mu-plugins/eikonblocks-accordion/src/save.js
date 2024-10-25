import { useBlockProps, RichText } from '@wordpress/block-editor';
import { colorMap } from './colorUtils';

const SaveComponent = (props) => {
  const { attributes } = props;
  const { items, backgroundColor, textColor } = attributes

  const bgColorSlug = colorMap[backgroundColor] || 'white';
  const textColorSlug = colorMap[textColor] || 'blue';

  return (
    <div {...useBlockProps.save()} className={`wp-block-eikonblocks-accordion bg-${bgColorSlug} text-${textColorSlug}`}>
      <div className="accordion-container">
        {items.map((item, index) => (
          <div key={index} className="accordion-item">
            <div className="accordion-title">
              <RichText.Content tagName="div" value={item.title} />
            </div>
            <div className="accordion-text">
              <RichText.Content tagName="div" value={item.text} />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default SaveComponent;
