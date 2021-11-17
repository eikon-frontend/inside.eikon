import Masonry from 'masonry-layout';
import imagesLoaded from 'imagesloaded';

const masonry = () => {
  [...document.querySelectorAll('.grid')].forEach(
    (gridElement) => {
      var msnry = new Masonry(gridElement, {
        columnWidth: gridElement.querySelector('.grid-sizer')[0],
        itemSelector: gridElement.querySelector('.grid-item')[0],
        percentPosition: true,
        gutter: 20
      });

      [...gridElement.querySelectorAll('.grid-item')].forEach(
        (gridItem) => {
          gridItem.querySelector('img').classList.add("hidden");
        }
      );

      window.onload = (event) => {
        msnry.layout();
      };

      const imgLoad = imagesLoaded(gridElement);

      imgLoad.on('progress', function (instance, image) {
        image.img.classList.remove("hidden");

        console.log("image loade", image);
        msnry.layout();
      });
    }
  );

};

export default masonry;
