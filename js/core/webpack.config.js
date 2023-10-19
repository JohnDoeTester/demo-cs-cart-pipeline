const path = require('path');
const webpack = require('webpack');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');

process.env.NODE_ENV='production';

module.exports = {
  mode: 'production',
  entry: {
    'core': path.resolve(__dirname, 'src', 'index.js'), 
    'notifications_center': path.resolve(__dirname, 'components/notifications_center', 'index.js'),
    'bottom_panel': path.resolve(__dirname, 'components/bottom_panel', 'index.js')
  },
  output: {
    path: path.resolve(__dirname, '..', 'tygh'),
    filename: '[name].js'
  },
  externals: {
    jquery: 'jQuery'
  },
  optimization: {
    minimizer: [
      new UglifyJsPlugin()
    ]
  },
  module: {
    rules: [
      {
        test: /\.m?js$/,
        exclude: /(node_modules|bower_components)/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env', 'babel-preset-react-app'],
          }
        }
      }
    ]
  }
};
