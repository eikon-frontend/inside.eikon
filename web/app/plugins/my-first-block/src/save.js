import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { title, content } = attributes;

  return (
    <div {...useBlockProps.save()}>
      <p className="title">{title}</p>
      <RichText.Content className="content" tagName="p" value={content} />
    </div>
  );
}
