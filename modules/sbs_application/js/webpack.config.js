const path = require('path');
const anuConfig = require('../../../contrib/anu_lms/js/webpack.config');

const pathTo = (file) => path.resolve(__dirname, file);

// SBS Sessions: ANU LMS components overrides.
const sbs_sessions_aliases = [
  'components/AudioWithLabel',
  'components/ContentNavigation',
  'components/DownloadCoursePopup',
  'pages/courses/PageTemplate',
  'pages/courses/Sections',
  'pages/courses/Section',
  'pages/lesson/index',
  'utilities/transform.courses',
  'utilities/transform.course',
  'utilities/transform.lesson',
  'utilities/removeTags',
  'utilities/decodeHTMLEntities',
].reduce((accumulator, file) => {
  accumulator['@anu/' + file] = pathTo('../../sbs_sessions/js/src/' + file);
  return accumulator;
}, {});

// ANU LMS Storyline: ANU LMS components overrides.
const sbs_storylines_files = [
  'utilities/transform.storylines',
  'components/StorylineLabel',
  'components/StorylineOption',
  'components/StorylinesSelector',
  'pages/storylines',
].reduce((accumulator, file) => {
  accumulator['@anu/' + file] = pathTo('../../anu_lms_storyline/js/src/' + file);
  return accumulator;
}, {});

module.exports = {
  ...anuConfig,
  entry: {
    // Overridden ANU LMS Courses page bundle.
    '../../../sbs_sessions/js/dist/courses': {
      import: path.resolve(__dirname, '../../../contrib/anu_lms/js/src/bundles/courses.js'),
      dependOn: 'vendors',
    },
    // Overridden ANU LMS Lesson page bundle.
    '../../../sbs_sessions/js/dist/lesson': {
      import: path.resolve(__dirname, '../../../contrib/anu_lms/js/src/bundles/lesson.js'),
      dependOn: 'vendors',
    },
    // Addition for ANU LMS by our custom ANU LMS Storyline module.
    '../../../anu_lms_storyline/js/dist/storylines': {
      import: path.resolve(__dirname, '../../anu_lms_storyline/js/src/bundles/storylines.js'),
      dependOn: 'vendors',
    },
    // SBS Activities checklist bundle.
    '../../../sbs_activities/js/dist/checklist': {
      import: path.resolve(__dirname, '../../sbs_activities/js/src/bundles/checklist.js'),
      dependOn: 'vendors',
    },
    // SBS Activities audio bundle.
    '../../../sbs_activities/js/dist/audio': {
      import: path.resolve(__dirname, '../../sbs_activities/js/src/bundles/audio.js'),
      dependOn: 'vendors',
    },
    // SBS toolbox bundle.
    '../../../sbs_activities/js/dist/toolbox': {
      import: path.resolve(__dirname, '../../sbs_activities/js/src/bundles/toolbox.js'),
      dependOn: 'vendors',
    },
    // SBS chat bundle.
    '../../../sbs_chat/js/dist/chat': {
      import: path.resolve(__dirname, '../../sbs_chat/js/src/bundles/chat.js'),
      dependOn: 'vendors',
    },
    '../../../sbs_activities/js/dist/serviceworker': {
      import: path.resolve(__dirname, '../../sbs_activities/js/src/bundles/serviceworker.js'),
    },
    // Shared modules across bundles to avoid code loading duplication.
    vendors: ['react', 'react-dom', 'react-router-dom', 'he', 'prop-types'],
  },
  module: {
    rules: [
      ...anuConfig.module.rules,
      // Enable TypeScript support.
      {
        test: /\.ts$|tsx/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env', '@babel/preset-react', '@babel/preset-typescript'],
            plugins: ['@babel/plugin-transform-runtime'],
          },
        },
      },
    ]
  },
  resolve: {
    extensions: ['.ts', '...'],
    modules: [path.resolve(__dirname, 'node_modules')],
    alias: {
      // ANU LMS overrides for SBS Storylines.
      ...sbs_storylines_files,
      // ANU LMS overrides for SBS Sessions module.
      ...sbs_sessions_aliases,
      '@anu/pages/courses$': pathTo('../../sbs_sessions/js/src/pages/courses/index'),
      '@anu/Application': path.resolve(__dirname, './src/Application'),
      // Default ANU LMS components.
      '@anu': path.resolve(__dirname, '../../../contrib/anu_lms/js/src'),
    },
  },
};
