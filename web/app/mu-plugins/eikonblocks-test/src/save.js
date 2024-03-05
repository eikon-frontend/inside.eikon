import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { title, content, backgroundColor } = attributes;

  return (
    <div {...useBlockProps.save()} style={{ backgroundColor: backgroundColor }}>
      <p className="title">{title}</p>
      <RichText.Content className="content" tagName="p" value={content} />
    </div>
  );
}
