import { useBlockProps } from '@wordpress/block-editor';
import { colorMap } from './colorUtils';

export default function Save({ attributes }) {
  const { title, items = [], backgroundColor, textColor } = attributes;

  // Get the color slugs from the hex values
  const bgColorSlug = colorMap[backgroundColor] || '';
  const textColorSlug = colorMap[textColor] || '';

  return (
    <div {...useBlockProps.save()} className={`wp-block-eikonblocks-numbers bg-${bgColorSlug} text-${textColorSlug}`}>
      <div className="numbers-container">
        <div className="item">
          <div className="item-arrow">
            <svg><use href="/img/icons.svg#long-arrow"></use></svg>
          </div>
          <h2 className="item-text">{title}</h2>
        </div>
        {items.map((item, index) => (
          <div key={index} className="item">
            <p className="item-number">{item.number}</p>
            <p className="item-text">{item.text}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
