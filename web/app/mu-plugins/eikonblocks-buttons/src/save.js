import { useBlockProps } from '@wordpress/block-editor';

// Mapping of hex values to color slugs
const colorMap = {
  '#0000DE': 'blue',
  '#000000': 'black',
  '#FFFFFF': 'white',
  '#FF2C00': 'red',
  '#FF5F1C': 'orange',
  '#FF3EAD': 'fuchsia',
  '#FFA1CE': 'pink',
  '#A000FF': 'violet',
};

export default function Save(props) {
  const { attributes } = props;
  const { items, backgroundColor, textColor } = attributes;

  // Get the color slugs from the hex values
  const bgColorSlug = colorMap[backgroundColor] || '';
  const textColorSlug = colorMap[textColor] || '';

  return (
    <div {...useBlockProps.save()} className={`wp-block-eikonblocks-buttons bg-${bgColorSlug} text-${textColorSlug}`}>
      <div className="buttons-container">
        {items.map((item, index) => {
          const buttonClass = item.style === 'outline' ? 'button-outline' : 'button-plain';
          return (
            <a className={`button ${buttonClass}`} key={index} href={item.url} target={item.opensInNewTab ? '_blank' : '_self'} rel="noopener noreferrer">
              {item.title}
            </a>
          );
        })}
      </div>
    </div>
  );
}
