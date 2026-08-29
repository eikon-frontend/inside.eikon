import {
  useBlockProps,
  MediaUpload,
  MediaUploadCheck,
  InnerBlocks,
  BlockControls,
  InspectorControls
} from '@wordpress/block-editor';
import { Button, Placeholder, ToolbarGroup, ToolbarButton, PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
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
                  {__('Remplacer l\'image', 'eikonblocks')}
                </ToolbarButton>
              )}
            />
            <ToolbarButton
              onClick={() => setAttributes({ imageUrl: '' })}
              isDestructive
            >
              {__('Supprimer l\'image', 'eikonblocks')}
            </ToolbarButton>
          </ToolbarGroup>
        </BlockControls>
      )}
      <InspectorControls>
        <PanelBody title={__('Position de l\'image', 'eikonblocks')}>
          <SelectControl
            label={__('Sélectionner la position de l\'image', 'eikonblocks')}
            value={imagePosition}
            options={[
              { label: __('Gauche', 'eikonblocks'), value: 'left' },
              { label: __('Droite', 'eikonblocks'), value: 'right' }
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
            <img src={imageUrl} alt={__('Image de la carte', 'eikonblocks')} />
          ) : (
            <Placeholder
              label={__('Image de la carte', 'eikonblocks')}
              instructions={__('Sélectionnez une image pour la carte.', 'eikonblocks')}
            >
              <MediaUpload
                onSelect={onSelectImage}
                allowedTypes={['image']}
                render={({ open }) => (
                  <Button onClick={open} isPrimary>
                    {__('Sélectionner l\'image', 'eikonblocks')}
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
