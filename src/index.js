import { render } from '@wordpress/element'

import BlockEditor from './block-editor'

window.GroundhoggBlockEditor = (root, content, onChange = () => {}) => {
  render(<BlockEditor blocks={content} onChange={onChange}></BlockEditor>, root)
}