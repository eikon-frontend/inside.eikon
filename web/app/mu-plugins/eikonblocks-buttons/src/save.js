import { useBlockProps } from '@wordpress/block-editor';

export default function Save(props) {
  const { attributes } = props;
  const { items, alignment } = attributes;

  return (
    <div {...useBlockProps.save()} className={`wp-block-eikonblocks-buttons alignment-${alignment}`}>
      <div className="buttons-container">
        {items.map((item, index) => {
          const buttonClass = item.style === 'outline' ? 'button-outline' : 'button-plain';
          return (
            <a className={`button ${buttonClass}`} key={index} href={item.url} target={item.opensInNewTab ? '_blank' : '_self'} rel="noopener noreferrer">
              {item.title}
              {item.icon && item.icon !== 'none' && (
                <svg className="icon">
                  <use href={`/img/icons.svg#${item.icon}`}></use>
                </svg>
              )}
            </a>
          );
        })}
      </div>
    </div>
  );
}
