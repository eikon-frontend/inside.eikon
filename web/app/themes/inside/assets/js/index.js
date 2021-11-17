import 'bootstrap';

import masonry from './components/masonry';
import photoswipe from './components/photoswipe';
import slider from './components/slider';
import scrolltrigger from './components/scrolltrigger';

const init = () => {
  masonry();
  photoswipe();
  slider();
  scrolltrigger();
};

document.addEventListener("DOMContentLoaded", function () {
  init();
});
