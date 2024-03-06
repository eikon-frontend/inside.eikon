import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save({ attributes }) {
  const { selectedPosts } = attributes;

  return (
    <div {...useBlockProps.save()}>
      {selectedPosts.map((post) => (
        <div key={post.id}>
          <RichText.Content tagName="h2" value={post.title.rendered} />
          <RichText.Content tagName="pre" value={JSON.stringify(post.slug, null, 2)} />
        </div>
      ))}
    </div>
  );
}
