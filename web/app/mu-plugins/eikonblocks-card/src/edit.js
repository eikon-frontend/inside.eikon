import {
  useBlockProps,
  MediaUpload,
  MediaUploadCheck,
  InnerBlocks,
  BlockControls,
  InspectorControls
} from '@wordpress/block-editor';
import { Button, Placeholder, ToolbarGroup, ToolbarButton, PanelBody, SelectControl } from '@wordpress/components';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;
  const { imageUrl, imagePosition } = attributes;

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
      <InspectorControls>
        <PanelBody title="Image Position">
          <SelectControl
            label="Select Image Position"
            value={imagePosition}
            options={[
              { label: 'Left', value: 'left' },
              { label: 'Right', value: 'right' }
            ]}
            onChange={(value) => setAttributes({ imagePosition: value })}
          />
        </PanelBody>
      </InspectorControls>
      <div className="eikonblock-content">
        <div className="card-content">
          <InnerBlocks />
        </div>
        <div className={`card-image card-image-${imagePosition}`}>
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
