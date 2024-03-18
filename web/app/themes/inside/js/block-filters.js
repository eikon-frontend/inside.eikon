wp.blocks.registerBlockType('eikonblocks/heading', {
  title: 'Titre',
  icon: 'heading',
  category: 'common',
  attributes: {
    content: {
      type: 'array',
      source: 'children',
      selector: 'h2,h3',
    },
    level: {
      type: 'number',
      default: 2,
    },
  },
  edit: function ({ attributes, setAttributes }) {
    var content = attributes.content;
    var level = attributes.level;

    return wp.element.createElement(
      'div',
      {},
      wp.element.createElement(
        wp.blockEditor.BlockControls,
        {},
        wp.element.createElement(
          wp.components.ToolbarGroup,
          {
            isCollapsed: true,
            icon: 'heading',
            label: 'Change heading level',
            controls: ['2', '3', '4', '5', '6'].map(function (index) {
              return {
                icon: 'heading',
                title: 'H' + index,
                isActive: level === index,
                onClick: function () {
                  setAttributes({ level: index });
                }
              };
            })
          }
        )
      ),
      wp.element.createElement(
        wp.blockEditor.RichText,
        {
          tagName: 'h' + level,
          value: content,
          onChange: function (newContent) {
            setAttributes({ content: newContent });
          },
          onSplit: function (value) {
            if (!value) {
              return wp.blocks.createBlock('myplugin/myheading');
            }
            return wp.blocks.createBlock('myplugin/myheading', {
              ...attributes,
              content: value,
            });
          },
          placeholder: 'Enter heading here...'
        }
      )
    );
  },
  save({ attributes }) {
    return wp.element.createElement('h' + attributes.level, {}, attributes.content);
  },
});
