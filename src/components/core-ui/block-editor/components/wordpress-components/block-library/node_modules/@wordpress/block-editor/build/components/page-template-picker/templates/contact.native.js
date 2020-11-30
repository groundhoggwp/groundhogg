"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _i18n = require("@wordpress/i18n");

/**
 * WordPress dependencies
 */
var Contact = {
  // translators: title for "Contact" page template
  name: (0, _i18n.__)('Contact'),
  key: 'contact',
  icon: '📫',
  content: [{
    name: 'core/paragraph',
    attributes: {
      // translators: sample content for "Contact" page template
      content: (0, _i18n.__)("Let's talk 👋 Don't hesitate to reach out with the contact information below, or send a message using the form.")
    }
  }, {
    name: 'core/spacer',
    attributes: {
      height: 24
    }
  }, {
    name: 'core/heading',
    attributes: {
      // translators: sample content for "Contact" page template
      content: (0, _i18n.__)('Get in Touch'),
      level: 2
    }
  }, {
    name: 'core/paragraph',
    attributes: {
      // translators: sample content for "Contact" page template
      content: (0, _i18n.__)('10 Street Road')
    }
  }, {
    name: 'core/paragraph',
    attributes: {
      // translators: sample content for "Contact" page template
      content: (0, _i18n.__)('City, 10100')
    }
  }, {
    name: 'core/paragraph',
    attributes: {
      // translators: sample content for "Contact" page template
      content: (0, _i18n.__)('USA')
    }
  }, {
    name: 'core/paragraph',
    attributes: {
      // translators: sample content for "Contact" page template
      content: (0, _i18n.__)('<a href="mailto:mail@example.com">mail@example.com</a>')
    }
  }, {
    name: 'core/paragraph',
    attributes: {
      // translators: sample content for "Contact" page template
      content: (0, _i18n.__)('(555)555-1234')
    }
  }]
};
var _default = Contact;
exports.default = _default;
//# sourceMappingURL=contact.native.js.map