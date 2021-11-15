import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";

gsap.registerPlugin(ScrollTrigger);

const scrolltrigger = () => {
  const tl = gsap.timeline({
    scrollTrigger: {
      trigger: ".scroll-container",
      scrub: 0.5,
      start: () => 'top ' + window.innerHeight * 1,
      end: () => 'bottom ' + window.innerHeight * 0.1,
      toggleActions: "restart pause pause reset", // onEnter, onLeave, onEnterBack, onLeaveBack
    },
  });
  tl.fromTo(".scroll-left", { xPercent: -50 }, { xPercent: 0 }, "timeline");
  tl.fromTo(".scroll-right", { xPercent: 0 }, { xPercent: -50 }, "timeline");
};

export default scrolltrigger;
