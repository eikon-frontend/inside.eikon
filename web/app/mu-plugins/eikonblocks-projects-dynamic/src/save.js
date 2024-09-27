import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
  const { selectedPosts, backgroundColor, textColor } = attributes;

  // Create an array of post IDs
  const postIds = selectedPosts.map(post => post.id);

  // Save the array as a JSON string in a data attribute
  return (
    <div className={`bg-${backgroundColor} text-${textColor}`}>
      <div {...useBlockProps.save()} data-post-ids={JSON.stringify(postIds)}></div>
    </div>
  );
}
