import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

export default function Save({ attributes }) {
  const { imageUrl } = attributes;

  return (
    <div {...useBlockProps.save()}>
      <div className="card-left">
        <InnerBlocks.Content />
      </div>
      <div className="card-right">
        {imageUrl && (
          <img src={imageUrl} alt="Card Image" />
        )}
      </div>
    </div>
  );
}
