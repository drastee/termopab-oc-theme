// 1. Импортируем JS
import { Fancybox } from "@fancyapps/ui";

import "@fancyapps/ui/dist/fancybox/fancybox.css";

Fancybox.bind('[data-fancybox="gallery"]', {

  dragToClose: false,
  Toolbar: {
    display: {
      left: ["infobar"],
      middle: [],
      right: ["close"],
    },
  },
});