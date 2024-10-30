wp.hooks.addFilter(
  "blocks.registerBlockType",
  "mno/block-features",
  customizeBlockFeatures
);

wp.blocks.unregisterBlockVariation('core/group', 'group-grid');

function customizeBlockFeatures(settings, name) {
  // Disable layout options for group blocks
  if (name === "core/group") {
    settings.supports.layout = false;
  }

  return settings;
}
