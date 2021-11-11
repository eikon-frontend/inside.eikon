import Masonry from 'masonry-layout';

const masonry = () => {
  [...document.querySelectorAll('.grid')].forEach(
    (gridElement) => {
      var msnry = new Masonry(gridElement, {
        columnWidth: gridElement.querySelector('.grid-sizer')[0],
        itemSelector: gridElement.querySelector('.grid-item')[0],
        percentPosition: true,
        gutter: 20
      });

      window.onload = (event) => {
        msnry.layout();
      };
    }
  );

};

export default masonry;
