"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var tableContentPasteSchema = function tableContentPasteSchema(_ref) {
  var phrasingContentSchema = _ref.phrasingContentSchema;
  return {
    tr: {
      allowEmpty: true,
      children: {
        th: {
          allowEmpty: true,
          children: phrasingContentSchema,
          attributes: ['scope']
        },
        td: {
          allowEmpty: true,
          children: phrasingContentSchema
        }
      }
    }
  };
};

var tablePasteSchema = function tablePasteSchema(args) {
  return {
    table: {
      children: {
        thead: {
          allowEmpty: true,
          children: tableContentPasteSchema(args)
        },
        tfoot: {
          allowEmpty: true,
          children: tableContentPasteSchema(args)
        },
        tbody: {
          allowEmpty: true,
          children: tableContentPasteSchema(args)
        }
      }
    }
  };
};

var transforms = {
  from: [{
    type: 'raw',
    selector: 'table',
    schema: tablePasteSchema
  }]
};
var _default = transforms;
exports.default = _default;
//# sourceMappingURL=transforms.js.map