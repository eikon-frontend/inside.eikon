(()=>{"use strict";const e=window.wp.blocks,t=window.React,o=window.wp.blockEditor,n=window.wp.i18n,l=JSON.parse('{"UU":"eikonblocks/buttons"}');(0,e.registerBlockType)(l.UU,{edit:function(e){const{attributes:l,setAttributes:r}=e,{items:a,backgroundColor:i,textColor:s}=l;return(0,t.createElement)(t.Fragment,null,(0,t.createElement)(o.InspectorControls,null,(0,t.createElement)(o.PanelColorSettings,{title:(0,n.__)("Color Settings","eikonblocks"),initialOpen:!0,colorSettings:[{value:i,onChange:e=>r({backgroundColor:e}),label:(0,n.__)("Background Color","eikonblocks")},{value:s,onChange:e=>r({textColor:e}),label:(0,n.__)("Text Color","eikonblocks")}]})),(0,t.createElement)("div",{...(0,o.useBlockProps)(),style:{backgroundColor:i,color:s,padding:"20px",borderRadius:"5px"}},a.map(((e,l)=>(0,t.createElement)("div",{key:l,style:{marginBottom:"20px",padding:"10px",background:"white",color:"black",border:"1px solid #ddd",borderRadius:"5px"}},(0,t.createElement)("label",{style:{display:"block",marginBottom:"5px"}},(0,n.__)("Titre du bouton","eikonblocks"),(0,t.createElement)("input",{type:"text",value:e.title||"",onChange:e=>((e,t)=>{const o=[...a];o[e]={...o[e],title:t},r({items:o})})(l,e.target.value),placeholder:(0,n.__)("Enter title","eikonblocks"),style:{width:"100%",padding:"8px",marginBottom:"10px",borderRadius:"3px",border:"1px solid #ccc"}})),(0,t.createElement)("label",{style:{display:"block",marginBottom:"5px"}},(0,n.__)("Lien","eikonblocks"),(0,t.createElement)(o.__experimentalLinkControl,{value:e,onChange:e=>((e,t)=>{const o=[...a];o[e]={...o[e],...t},r({items:o})})(l,e),settings:[{id:"opensInNewTab",title:(0,n.__)("Open in new tab","eikonblocks")}],style:{width:"100%"}}))))),(0,t.createElement)("button",{onClick:()=>{r({items:[...a,{url:"",opensInNewTab:!1,title:""}]})},style:{padding:"10px 20px",backgroundColor:"#007cba",color:"#fff",border:"none",borderRadius:"10px",cursor:"pointer"}},(0,n.__)("Ajouter un bouton","eikonblocks"))))},save:function(e){const{attributes:n}=e,{items:l,backgroundColor:r,textColor:a}=n;return(0,t.createElement)("div",{...o.useBlockProps.save(),style:{backgroundColor:r,color:a}},(0,t.createElement)("div",{className:"buttons-container"},l.map(((e,o)=>(0,t.createElement)("a",{className:"button",key:o,href:e.url,target:e.opensInNewTab?"_blank":"_self",rel:"noopener noreferrer"},e.title)))))}})})();