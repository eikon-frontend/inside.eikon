import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save({ attributes }) {
  const { selectedPosts, backgroundColor, textColor } = attributes;

  return (
    <div {...useBlockProps.save()} className={`wp-block-eikonblocks-projects bg-${backgroundColor} text-${textColor}`} >
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
