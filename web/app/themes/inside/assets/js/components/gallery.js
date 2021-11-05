import * as PhotoSwipe from 'photoswipe';
import * as PhotoSwipeUIDefault from 'photoswipe/dist/photoswipe-ui-default';

const gallery = () => {
  const pswpElement = document.querySelectorAll('.pswp')[0];
  const galleries = document.querySelectorAll('.sisp-gallery');
  const options = {
    // optionName: 'option value'
    // for example:
    index: 0, // start at first slide
    history: false,
  };

  const openPhotoSwipe = (psGallery) => {
    const images = psGallery.querySelector('.slides').children;

    const galleryItems = [];
    for (let i = 0; i < images.length; i += 1) {
      galleryItems.push({
        src: images[i].querySelector('img').src,
        w: images[i].querySelector('img').dataset.w,
        h: images[i].querySelector('img').dataset.h,
      });
    }

    // Initializes and opens PhotoSwipe
    const photoSwipeGallery = new PhotoSwipe(
      pswpElement,
      PhotoSwipeUIDefault,
      galleryItems,
      options,
    );
    photoSwipeGallery.init();
  };

  for (let i = 0; i < galleries.length; i += 1) {
    galleries[i].querySelector('.open-gallery').onclick = () =>
      openPhotoSwipe(galleries[i]);
  }
};

export default gallery;
