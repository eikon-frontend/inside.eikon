(()=>{"use strict";const e=window.wp.blocks,t=window.React,l=window.wp.blockEditor,o=window.wp.components,a=window.wp.i18n,n=JSON.parse('{"UU":"eikonblocks/marquee"}');(0,e.registerBlockType)(n.UU,{edit:function(e){const{attributes:n,setAttributes:r}=e,{content:c,backgroundColor:i,textColor:s}=n,u=[{label:"Blue",value:"blue"},{label:"Black",value:"black"},{label:"White",value:"white"},{label:"Red",value:"red"},{label:"Orange",value:"orange"},{label:"Fuchsia",value:"fuchsia"},{label:"Pink",value:"pink"},{label:"Violet",value:"violet"}];return(0,t.createElement)(t.Fragment,null,(0,t.createElement)(l.InspectorControls,null,(0,t.createElement)(o.PanelBody,{title:"Color Settings"},(0,t.createElement)(o.SelectControl,{label:"Background Color",value:i,options:u,onChange:e=>r({backgroundColor:e})}),(0,t.createElement)(o.SelectControl,{label:"Text Color",value:s,options:u,style:{width:"100%"},onChange:e=>r({textColor:e})}))),(0,t.createElement)("div",{...(0,l.useBlockProps)(),style:{backgroundColor:i,color:s}},(0,t.createElement)(l.RichText,{tagName:"p",value:c,onChange:e=>r({content:e}),placeholder:(0,a.__)("Add your custom text","eikonblocks"),allowedFormats:["core/italic"],style:{fontSize:"60px",padding:"0",margin:"0"}})))},save:function({attributes:e}){const{content:o,backgroundColor:a,textColor:n}=e,r=o.length/5+"s";return(0,t.createElement)("div",{...l.useBlockProps.save(),className:`wp-block-eikonblocks-marquee bg-${a} text-${n}`},(0,t.createElement)("div",{className:"marquee-container"},(0,t.createElement)(l.RichText.Content,{className:"content",tagName:"p","data-text":o,value:o,style:{animationDuration:r}})))}})})();