const colorMap = {
  '#0000DE': 'blue',
  '#000000': 'black',
  '#FFFFFF': 'white',
  '#FF2C00': 'red',
  '#FF5F1C': 'orange',
  '#FF3EAD': 'fuchsia',
  '#FFA1CE': 'pink',
  '#A000FF': 'violet',
};

wp.blocks.registerBlockType('eikonblocks/heading', {
  title: 'Titre',
  icon: 'heading',
  category: 'common',
  attributes: {
    content: {
      type: 'string',
      source: 'html',
      selector: 'h2,h3,h4',
    },
    level: {
      type: 'number',
      default: 2,
    },
    backgroundColor: {
      type: 'string',
      default: 'white',
    },
    textColor: {
      type: 'string',
      default: 'black',
    },
  },
  edit: function ({ attributes, setAttributes }) {
    var content = attributes.content;
    var level = attributes.level;
    var backgroundColor = attributes.backgroundColor;
    var textColor = attributes.textColor;

    const headingStyle = {
      color: textColor,
      backgroundColor: backgroundColor,
      padding: '20px',
    };

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
            controls: ['2', '3', '4'].map(function (index) {
              return {
                icon: 'heading',
                title: 'H' + index,
                isActive: level === parseInt(index),
                onClick: function () {
                  setAttributes({ level: parseInt(index) });
                }
              };
            })
          }
        )
      ),
      wp.element.createElement(
        wp.blockEditor.InspectorControls,
        {},
        wp.element.createElement(
          wp.blockEditor.PanelColorSettings,
          {
            title: 'Color Settings',
            initialOpen: true,
            colorSettings: [
              {
                value: backgroundColor,
                onChange: function (newColor) {
                  setAttributes({ backgroundColor: newColor || 'white' });
                },
                label: 'Background Color',
              },
              {
                value: textColor,
                onChange: function (newColor) {
                  setAttributes({ textColor: newColor || 'black' });
                },
                label: 'Text Color',
              },
            ],
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
          placeholder: 'Enter heading here...',
          allowedFormats: ['core/italic'],
          style: headingStyle,
        }
      )
    );
  },
  save({ attributes }) {
    const textColorName = colorMap[attributes.textColor] || attributes.textColor;
    const backgroundColorName = colorMap[attributes.backgroundColor] || attributes.backgroundColor;

    const className = `text-${textColorName} bg-${backgroundColorName}`;

    return wp.element.createElement(
      wp.blockEditor.RichText.Content,
      {
        tagName: 'h' + attributes.level,
        className: className,
        value: attributes.content,
      }
    );
  },
});
