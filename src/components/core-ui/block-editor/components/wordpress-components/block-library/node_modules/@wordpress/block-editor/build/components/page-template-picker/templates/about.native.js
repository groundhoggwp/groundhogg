"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _i18n = require("@wordpress/i18n");

/**
 * WordPress dependencies
 */
var About = {
  // translators: title for "About" page template
  name: (0, _i18n.__)('About'),
  key: 'about',
  icon: '👋',
  content: [{
    name: 'core/paragraph',
    attributes: {
      // translators: sample content for "About" page template
      content: (0, _i18n.__)('Visitors will want to know who is on the other side of the page. Use this space to write about yourself, your site, your business, or anything you want. Use the testimonials below to quote others, talking about the same thing – in their own words.')
    }
  }, {
    name: 'core/paragraph',
    attributes: {
      // translators: sample content for "About" page template
      content: (0, _i18n.__)('This is sample content, included with the template to illustrate its features. Remove or replace it with your own words and media.')
    }
  }, {
    name: 'core/heading',
    attributes: {
      // translators: sample content for "About" page template
      content: (0, _i18n.__)('What People Say'),
      level: 2
    }
  }, {
    name: 'core/columns',
    innerBlocks: [{
      name: 'core/column',
      innerBlocks: [{
        name: 'core/quote',
        attributes: {
          // translators: sample content for "About" page template
          value: "<p>".concat((0, _i18n.__)('The way to get started is to quit talking and begin doing.'), "</p>"),
          // translators: sample content for "About" page template
          citation: (0, _i18n.__)('Walt Disney')
        }
      }]
    }, {
      name: 'core/column',
      innerBlocks: [{
        name: 'core/quote',
        attributes: {
          // translators: sample content for "About" page template
          value: "<p>".concat((0, _i18n.__)('It is our choices, Harry, that show what we truly are, far more than our abilities.'), "</p>"),
          // translators: sample content for "About" page template
          citation: (0, _i18n.__)('J.K. Rowling')
        }
      }]
    }, {
      name: 'core/column',
      innerBlocks: [{
        name: 'core/quote',
        attributes: {
          // translators: sample content for "About" page template
          value: "<p>".concat((0, _i18n.__)('Don’t cry because it’s over, smile because it happened.'), "</p>"),
          // translators: sample content for "About" page template
          citation: (0, _i18n.__)('Dr. Seuss')
        }
      }]
    }]
  }, {
    name: 'core/spacer',
    attributes: {
      height: 24
    }
  }, {
    name: 'core/separator',
    attributes: {}
  }, {
    name: 'core/spacer',
    attributes: {
      height: 24
    }
  }, {
    name: 'core/heading',
    attributes: {
      align: 'center',
      // translators: sample content for "About" page template
      content: (0, _i18n.__)('Let’s build something together!'),
      level: 2
    }
  }, {
    name: 'core/buttons',
    attributes: {
      align: 'center'
    },
    innerBlocks: [{
      name: 'core/button',
      attributes: {
        // translators: sample content for "About" page template
        text: (0, _i18n.__)('Get in Touch')
      }
    }]
  }, {
    name: 'core/spacer',
    attributes: {
      height: 24
    }
  }, {
    name: 'core/separator',
    attributes: {}
  }]
};
var _default = About;
exports.default = _default;
//# sourceMappingURL=about.native.js.map