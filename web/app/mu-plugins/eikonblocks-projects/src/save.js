import { useBlockProps, RichText } from '@wordpress/block-editor';
import { colorMap } from './colorUtils';

export default function save({ attributes }) {
  const { selectedPosts, backgroundColor, textColor } = attributes;

  // Get the color slugs from the hex values
  const bgColorSlug = colorMap[backgroundColor] || '';
  const textColorSlug = colorMap[textColor] || '';

  return (
    <div {...useBlockProps.save()} className={`wp-block-eikonblocks-projects bg-${bgColorSlug} text-${textColorSlug}`}>
      {
        selectedPosts.map((post) => (
          <div className="project" key={post.id}>
            <a href={`/projets/${post.slug}`}>
              <RichText.Content tagName="h2" value={post.title.rendered} />
            </a>
            {post.featured_image_src && (
              <img src={post.featured_image_src} alt={post.title.rendered} />
            )}
          </div>
        ))
      }
    </div>
  );
}
