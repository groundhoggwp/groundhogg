import React from 'react';

const BlocksDivider = ({stroke, fill, fillSecondary}) => (
  <svg width="34" height="34" viewBox="0 0 34 34" fill="none">
      <rect x="5" y="10" width="24" height="5" fill={fillSecondary} stroke="none" rx="2" />
      <rect class="svgHighlighted" x="0" y="19" width="34" height="3" fill={"#000"} stroke="none" />
      <rect x="5" y="25" width="24" height="5" fill={fillSecondary} stroke="none" rx="2" />
  </svg>

);

export default BlocksDivider;
