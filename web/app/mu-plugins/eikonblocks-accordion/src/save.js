import { useBlockProps, RichText } from '@wordpress/block-editor';

const SaveComponent = (props) => {
  const { attributes } = props;
  const { items } = attributes

  return (
    <div {...useBlockProps.save()} className="wp-block-eikonblocks-accordion">
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
