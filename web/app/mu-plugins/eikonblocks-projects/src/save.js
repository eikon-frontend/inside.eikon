import { useBlockProps } from '@wordpress/block-editor';
import { colorMap } from './colorUtils';

export default function save({ attributes }) {
  const { selectedPosts, backgroundColor, textColor } = attributes;

  // Get the color slugs from the hex values
  const bgColorSlug = colorMap[backgroundColor] || '';
  const textColorSlug = colorMap[textColor] || '';

  // Create an array of post IDs
  const postIds = selectedPosts.map(post => post.id);

  // Save the array as a JSON string in a data attribute
  return (
    <div {...useBlockProps.save()} data-post-ids={JSON.stringify(postIds)} className={`wp-block-eikonblocks-projects bg-${bgColorSlug} text-${textColorSlug}`}>
    </div>
  );
}
