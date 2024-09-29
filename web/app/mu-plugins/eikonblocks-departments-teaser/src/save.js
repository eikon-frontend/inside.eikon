import { useBlockProps } from '@wordpress/block-editor';

export default function save({ attributes }) {
  const { selectedPosts } = attributes;

  // Create an array of post IDs
  const postIds = selectedPosts.map(post => post.id);

  // Save the array as a JSON string in a data attribute
  return (
    <div class="wp-block-eikonblocks-departments-teaser"  {...useBlockProps.save()} data-post-ids={JSON.stringify(postIds)}></div>
  );
}
