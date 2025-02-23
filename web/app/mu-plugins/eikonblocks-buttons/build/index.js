/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/edit.js":
/*!*********************!*\
  !*** ./src/edit.js ***!
  \*********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Edit)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./editor.scss */ "./src/editor.scss");




function Edit(props) {
  const {
    attributes,
    setAttributes
  } = props;
  const {
    items,
    alignment
  } = attributes;
  const handleItemChange = (index, value) => {
    const newItems = [...items];
    newItems[index] = {
      ...newItems[index],
      ...value
    };
    setAttributes({
      items: newItems
    });
  };
  const handleTitleChange = (index, title) => {
    const newItems = [...items];
    newItems[index] = {
      ...newItems[index],
      title
    };
    setAttributes({
      items: newItems
    });
  };
  const handleStyleChange = (index, style) => {
    const newItems = [...items];
    newItems[index] = {
      ...newItems[index],
      style
    };
    setAttributes({
      items: newItems
    });
  };
  const handleIconChange = (index, icon) => {
    const newItems = [...items];
    newItems[index] = {
      ...newItems[index],
      icon
    };
    setAttributes({
      items: newItems
    });
  };
  const handleUrlChange = (index, url) => {
    const newItems = [...items];
    newItems[index] = {
      ...newItems[index],
      url
    };
    setAttributes({
      items: newItems
    });
  };
  const handleOpenInNewTabChange = (index, opensInNewTab) => {
    const newItems = [...items];
    newItems[index] = {
      ...newItems[index],
      opensInNewTab
    };
    setAttributes({
      items: newItems
    });
  };
  const addItem = () => {
    setAttributes({
      items: [...items, {
        url: '',
        opensInNewTab: false,
        title: '',
        style: 'plain',
        icon: 'arrow'
      }]
    });
  };
  const removeItem = index => {
    const newItems = [...items];
    newItems.splice(index, 1);
    setAttributes({
      items: newItems
    });
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.BlockControls, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.AlignmentToolbar, {
    value: alignment,
    onChange: newAlignment => setAttributes({
      alignment: newAlignment || 'left'
    })
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ...(0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockProps)()
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "eikonblock-title"
  }, "eikonblock // buttons"), items.map((item, index) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: index,
    style: {
      marginBottom: '5px',
      padding: '10px',
      background: 'white',
      color: 'black',
      border: '1px solid #ddd',
      borderRadius: '5px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("table", {
    style: {
      width: '90%'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tbody", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
    style: {
      width: '30%',
      verticalAlign: 'top',
      paddingRight: '10px',
      paddingTop: '6px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Label', 'eikonblocks'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    value: item.title || '',
    onChange: e => handleTitleChange(index, e.target.value),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Enter title', 'eikonblocks'),
    style: {
      width: '90%',
      padding: '8px',
      borderRadius: '3px',
      border: '1px solid #ccc'
    }
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
    style: {
      verticalAlign: 'top',
      paddingRight: '10px',
      paddingTop: '6px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Lien', 'eikonblocks'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "text",
    value: item.url || '',
    onChange: e => handleUrlChange(index, e.target.value),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Enter URL', 'eikonblocks'),
    style: {
      width: '90%',
      padding: '8px',
      borderRadius: '3px',
      border: '1px solid #ccc'
    }
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
    style: {
      verticalAlign: 'top',
      paddingRight: '10px',
      paddingTop: '6px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Open in new tab', 'eikonblocks'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
    style: {
      textAlign: 'left'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
    type: "checkbox",
    checked: item.opensInNewTab,
    onChange: e => handleOpenInNewTabChange(index, e.target.checked)
  }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
    style: {
      verticalAlign: 'top',
      paddingRight: '10px',
      paddingTop: '6px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Style', 'eikonblocks'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: item.style,
    onChange: e => handleStyleChange(index, e.target.value),
    style: {
      width: '90%',
      padding: '8px',
      borderRadius: '3px',
      border: '1px solid #ccc'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "plain"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Plain', 'eikonblocks')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "outline"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Outline', 'eikonblocks'))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("tr", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", {
    style: {
      verticalAlign: 'top',
      paddingRight: '10px',
      paddingTop: '6px'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Icon', 'eikonblocks'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("td", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("select", {
    value: item.icon,
    onChange: e => handleIconChange(index, e.target.value),
    style: {
      width: '90%',
      padding: '8px',
      borderRadius: '3px',
      border: '1px solid #ccc'
    }
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "none"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('None', 'eikonblocks')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "arrow"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Arrow', 'eikonblocks')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "download"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Download', 'eikonblocks')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("option", {
    value: "external"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('External', 'eikonblocks'))))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: () => removeItem(index),
    style: {
      marginTop: '10px',
      padding: '8px 16px',
      backgroundColor: '#d63638',
      color: '#fff',
      border: 'none',
      borderRadius: '5px',
      cursor: 'pointer'
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Supprimer le bouton', 'eikonblocks')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("button", {
    onClick: addItem,
    style: {
      marginTop: '10px',
      padding: '8px 16px',
      backgroundColor: '#333',
      color: '#fff',
      border: 'none',
      borderRadius: '5px',
      cursor: 'pointer'
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Ajouter un bouton', 'eikonblocks'))));
}

/***/ }),

/***/ "./src/save.js":
/*!*********************!*\
  !*** ./src/save.js ***!
  \*********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Save)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__);


function Save(props) {
  const {
    attributes
  } = props;
  const {
    items,
    alignment
  } = attributes;
  if (!items || !Array.isArray(items)) {
    return null;
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    ..._wordpress_block_editor__WEBPACK_IMPORTED_MODULE_1__.useBlockProps.save(),
    className: `wp-block-eikonblocks-buttons alignment-${alignment}`
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "buttons-container"
  }, items.map((item, index) => {
    const buttonClass = item.style === 'outline' ? 'button-outline' : 'button-plain';
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
      className: `button ${buttonClass}`,
      key: index,
      href: item.url,
      target: item.opensInNewTab ? '_blank' : '_self',
      rel: "noopener noreferrer"
    }, item.title, item.icon && item.icon !== 'none' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
      className: "icon"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("use", {
      href: `/img/icons.svg#${item.icon}`
    })));
  })));
}

/***/ }),

/***/ "./src/editor.scss":
/*!*************************!*\
  !*** ./src/editor.scss ***!
  \*************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ ((module) => {

module.exports = window["wp"]["blockEditor"];

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ ((module) => {

module.exports = window["wp"]["blocks"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "./src/block.json":
/*!************************!*\
  !*** ./src/block.json ***!
  \************************/
/***/ ((module) => {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"eikonblocks/buttons","version":"0.1.0","title":"Buttons","category":"widgets","icon":"button","description":"Number block scaffolded with Create Block tool.","example":{},"supports":{"html":false},"textdomain":"eikonblocks","editorScript":"file:./index.js","editorStyle":"file:./index.css","parent":["eikonblocks/section"],"attributes":{"items":{"type":"array","default":[],"items":{"type":"object","properties":{"title":{"type":"string","default":""},"url":{"type":"string","default":""},"opensInNewTab":{"type":"boolean","default":false},"style":{"type":"string","default":"plain"},"icon":{"type":"string","default":"none"}}}},"alignment":{"type":"string","default":"left"}}}');

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _edit__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./edit */ "./src/edit.js");
/* harmony import */ var _save__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./save */ "./src/save.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./block.json */ "./src/block.json");




const el = wp.element.createElement;
const icon = el('svg', {
  width: 24,
  height: 24
}, el('path', {
  d: "M3 3h18c1.1 0 2 .9 2 2v14c0 1.1-.9 2-2 2H3c-1.1 0-2-.9-2-2V5c0-1.1.9-2 2-2Zm0 16h18v-3H3v3Z"
}));
(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_3__.name, {
  edit: _edit__WEBPACK_IMPORTED_MODULE_1__["default"],
  save: _save__WEBPACK_IMPORTED_MODULE_2__["default"],
  icon,
  allowedBlocks: _block_json__WEBPACK_IMPORTED_MODULE_3__.allowedBlocks
});
/******/ })()
;
//# sourceMappingURL=index.js.map