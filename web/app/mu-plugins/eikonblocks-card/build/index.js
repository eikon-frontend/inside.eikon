(()=>{"use strict";const e=window.wp.blocks,t=window.React,c=window.wp.blockEditor,l=window.wp.components,a=JSON.parse('{"UU":"eikonblocks/card"}'),n=wp.element.createElement,r=n("svg",{width:24,height:24},n("path",{d:"M2 5h12c.55 0 1 .45 1 1v12c0 .55 - .45 1 - 1 1H2c - .55 0 - 1 - .45 - 1 - 1V6c0 - .55.45 - 1 1 - 1Zm15 14h2V5h - 2v14Zm4 - 14v14h2V5h - 2ZM8 7.75c1.24 0 2.25 1.01 2.25 2.25S9.24 12.25 8 12.25 5.75 11.24 5.75 10 6.76 7.75 8 7.75Zm - 4.5 8.5V17h9v - .75c0 - 1.5 - 3 - 2.25 - 4.5 - 2.25s - 4.5.75 - 4.5 2.25Z"}));(0,e.registerBlockType)(a.UU,{edit:function(e){const{attributes:a,setAttributes:n}=e,{imageUrl:r}=a;return(0,t.createElement)("div",{...(0,c.useBlockProps)()},(0,t.createElement)("div",{className:"eikonblock-title"},"eikonblock // card"),(0,t.createElement)("div",{className:"eikonblock-content"},(0,t.createElement)("div",{className:"eikonblock-left"},(0,t.createElement)(c.InnerBlocks,null)),(0,t.createElement)("div",{className:"eikonblock-right"},r?(0,t.createElement)("img",{src:r,alt:"Card Image"}):(0,t.createElement)(c.MediaUploadCheck,null,(0,t.createElement)(c.MediaUpload,{onSelect:e=>{n({imageUrl:e.url})},allowedTypes:["image"],render:({open:e})=>(0,t.createElement)(l.Button,{onClick:e},"Select Image")})))))},save:function({attributes:e}){const{imageUrl:l}=e;return(0,t.createElement)("div",{...c.useBlockProps.save()},(0,t.createElement)("div",{className:"card-left"},(0,t.createElement)(c.InnerBlocks.Content,null)),(0,t.createElement)("div",{className:"card-right"},l&&(0,t.createElement)("img",{src:l,alt:"Card Image"})))},icon:r})})();