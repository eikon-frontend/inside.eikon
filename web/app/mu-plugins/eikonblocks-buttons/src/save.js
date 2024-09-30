import { useBlockProps } from '@wordpress/block-editor';

export default function Save(props) {
  const { attributes } = props;
  const { items, backgroundColor, textColor } = attributes;

  return (
    <div {...useBlockProps.save()} style={{ backgroundColor: backgroundColor, color: textColor }}>
      <div className="buttons-container">
        {items.map((item, index) => {
          return (
            <a className='button' key={index} href={item.url} target={item.opensInNewTab ? '_blank' : '_self'} rel="noopener noreferrer">
              {item.title}
            </a>
          );
        })}
      </div>
    </div>
  );
}
