import Masonry from 'masonry-layout';

const masonry = () => {
  var msnry = new Masonry('.grid', {
    columnWidth: '.grid-sizer',
    itemSelector: '.grid-item',
    percentPosition: true,
    gutter: 20
  });
};

export default masonry;
