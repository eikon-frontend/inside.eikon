const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const devMode = process.env.NODE_ENV !== 'production';
const FileManagerPlugin = require('filemanager-webpack-plugin');
const SVGSpritemapPlugin = require('svg-spritemap-webpack-plugin');
const webpack = require('webpack');

module.exports = {
  entry: ['./assets/js/index.js', './assets/scss/main.scss'],
  plugins: [
    new MiniCssExtractPlugin({
      filename: devMode ? '[name].css' : '[name].[hash].css',
      chunkFilename: devMode ? '[id].css' : '[id].[hash].css',
    }),
    new FileManagerPlugin({
      onEnd: [
        {
          copy: [
            {
              source: path.join(__dirname, 'assets/img'),
              destination: path.join(__dirname, 'theme/dist/img'),
            },
            {
              source: path.join(__dirname, 'assets/favicon'),
              destination: path.join(__dirname, 'theme/dist/favicon'),
            },
          ],
        },
      ],
    }),
    new SVGSpritemapPlugin(path.join(__dirname, 'assets/icons/*.svg'), {
      output: { filename: 'icons.svg' },
      sprite: { prefix: 'icon-' },
    }),
    new webpack.ProvidePlugin({
      $: 'jquery',
      jQuery: 'jquery',
    }),
  ],
  output: {
    filename: 'main.js',
    path: path.resolve(__dirname, 'theme/dist'),
  },
  module: {
    rules: [
      {
        test: /\.scss$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              hmr: process.env.NODE_ENV === 'development',
            },
          },
          'css-loader',
          'postcss-loader',
          'sass-loader',
        ],
      },
      {
        test: /\.(woff|woff2|eot|ttf|svg)$/,
        exclude: /node_modules/,
        loader: 'url-loader?limit=1024&name=fonts/[name].[ext]',
      },
      {
        test: /\.(jpg|jpeg|gif|png)$/,
        exclude: /node_modules/,
        loader: 'url-loader?limit=1024',
      },
    ],
  },
};
