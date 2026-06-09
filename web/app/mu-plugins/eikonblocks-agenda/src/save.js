import { useBlockProps } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { items = [] } = attributes;

  return (
    <div {...useBlockProps.save()} className="wp-block-eikonblocks-agenda">
      <div className="agenda-list">
        {items.map((item, index) => {
          const hasLink = !!item.link?.url;
          const isExternal = hasLink && item.link.opensInNewTab;
          const iconId = isExternal ? 'external' : 'arrow';
          const Tag = hasLink ? 'a' : 'div';
          const linkProps = hasLink
            ? {
              href: item.link.url,
              target: isExternal ? '_blank' : '_self',
              rel: isExternal ? 'noreferrer noopener' : undefined,
            }
            : {};

          return (
            <Tag
              key={index}
              className={`agenda-item${hasLink ? ` agenda-item--${isExternal ? 'external' : 'internal'}` : ''}`}
              {...linkProps}
            >
              <span className="agenda-item-date">{item.date}</span>
              <span className="agenda-item-title">{item.title}</span>
              {hasLink && (
                <span className="agenda-item-cta">
                  {item.link.label || 'En savoir plus'}
                  <svg className="icon">
                    <use href={`/img/icons.svg#${iconId}`}></use>
                  </svg>
                </span>
              )}
            </Tag>
          );
        })}
      </div>
    </div>
  );
}
