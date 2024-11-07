import {
  useBlockProps,
  MediaUpload,
  MediaUploadCheck,
  InnerBlocks,
  BlockControls
} from '@wordpress/block-editor';
import { Button, Placeholder, ToolbarGroup, ToolbarButton } from '@wordpress/components';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;
  const { imageUrl } = attributes;

  const onSelectImage = (media) => {
    setAttributes({ imageUrl: media.url });
  };

  return (
    <div {...useBlockProps()}>
      <div className='eikonblock-title'>eikonblock // card</div>
      {imageUrl && (
        <BlockControls>
          <ToolbarGroup>
            <MediaUpload
              onSelect={onSelectImage}
              allowedTypes={['image']}
              render={({ open }) => (
                <ToolbarButton onClick={open}>
                  Replace Image
                </ToolbarButton>
              )}
            />
            <ToolbarButton
              onClick={() => setAttributes({ imageUrl: '' })}
              isDestructive
            >
              Remove Image
            </ToolbarButton>
          </ToolbarGroup>
        </BlockControls>
      )}
      <div className="eikonblock-content">
        <div className="eikonblock-left">
          <InnerBlocks />
        </div>
        <div className="eikonblock-right">
          {imageUrl ? (
            <img src={imageUrl} alt="Card Image" />
          ) : (
            <Placeholder
              label="Card Image"
              instructions="Select an image for the card."
            >
              <MediaUpload
                onSelect={onSelectImage}
                allowedTypes={['image']}
                render={({ open }) => (
                  <Button onClick={open} isPrimary>
                    Select Image
                  </Button>
                )}
              />
            </Placeholder>
          )}
        </div>
      </div>
    </div>
  );
}
