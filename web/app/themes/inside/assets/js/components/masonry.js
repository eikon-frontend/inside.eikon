import Masonry from 'masonry-layout';

const masonry = () => {
  var msnry = new Masonry('.grid', {
    columnWidth: '.grid-sizer',
    itemSelector: '.grid-item',
    percentPosition: true,
    gutter: 20
  });

  window.onload = (event) => {
    msnry.layout();
  };
};

export default masonry;
