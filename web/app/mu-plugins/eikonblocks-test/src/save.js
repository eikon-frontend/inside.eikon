import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { title, content, backgroundColor, url } = attributes;

  return (
    <div {...useBlockProps.save()} style={{ backgroundColor: backgroundColor }}>
      <a href={url} className="url"><h2 className='title'>{title}</h2></a>
      <RichText.Content className="content" tagName="p" value={content} />
    </div>
  );
}
