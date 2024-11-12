import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { imageUrl, imagePosition } = attributes;

  return (
    <div {...useBlockProps.save()}>
      <div className="card-content">
        <InnerBlocks.Content />
      </div>
      <div className={`card-image card-image-${imagePosition}`}>
        {imageUrl && (
          <img src={imageUrl} alt="Card Image" />
        )}
      </div>
    </div>
  );
}
