import {
  useBlockProps,
  MediaUpload,
  MediaUploadCheck,
  InnerBlocks
} from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import './editor.scss';

export default function Edit(props) {
  const { attributes, setAttributes } = props;
  const { imageUrl } = attributes;

  const onSelectImage = (media) => {
    setAttributes({ imageUrl: media.url });
  };

  return (
    <div {...useBlockProps()}>
      <div className='eikonblock-title'>eikonblock // grid</div>
      <div className="eikonblock-content">
        <div className="eikonblock-left">
          <InnerBlocks />
        </div>
        <div className="eikonblock-right">
          {imageUrl ? (
            <img src={imageUrl} alt="Card Image" />
          ) : (
            <MediaUploadCheck>
              <MediaUpload
                onSelect={onSelectImage}
                allowedTypes={['image']}
                render={({ open }) => (
                  <Button onClick={open}>Select Image</Button>
                )}
              />
            </MediaUploadCheck>
          )}
        </div>
      </div>
    </div>
  );
}
