// import Swiper JS
import Swiper from 'swiper';

const slider = () => {
  [...document.querySelectorAll('.swiper')].forEach(
    (swiperElement) => {
      const slider = new Swiper(swiperElement, {
        loop: true,
        autoHeight: true,
      });

      // Adds prev button
      swiperElement
        .querySelector('.swiper-prev')
        .addEventListener('click', () => {
          slider.slidePrev();
        }, false);

      // Adds next button
      swiperElement
        .querySelector('.swiper-next')
        .addEventListener('click', () => {
          slider.slideNext();
        }, false);

    },
  );

};

export default slider;
