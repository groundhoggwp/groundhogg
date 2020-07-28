import React from 'react'

export class TitleInput extends React.Component {

  constructor (props) {
    super(props)

    this.state = {
      editing: false
    }

    this.handleChange = this.handleChange.bind(this)
    this.handleBlur = this.handleBlur.bind(this)
    this.handleClick = this.handleClick.bind(this)
    this.handleKeyDown = this.handleKeyDown.bind(this)

    this.titleInput = React.createRef()
  }

  handleChange (e) {

    const name = e.target.value
    this.props.onChange(name)
  }

  handleBlur (e) {
    this.setState({ editing: false })
    const name = e.target.value
    this.props.onBlur(name)
  }

  handleKeyDown (e) {
    if (e.keyCode === 13) {
      this.setState({ editing: false })
      const name = e.target.value
      this.props.onBlur(name)
    }
  }

  handleClick (e) {
    this.setState({ editing: true })
  }

  componentDidMount () {
    if (this.state.editing) {
      this.titleInput.current.focus()
    }
  }

  componentDidUpdate (prevProps, prevState, snapshot) {
    if (this.state.editing) {
      this.titleInput.current.focus()
    }
  }

  render () {

    if (this.state.editing) {
      return <input
        type={'text'}
        ref={this.titleInput}
        className={this.props.className +
        ' title-input title-input-editing'}
        value={this.props.title}
        onChange={this.handleChange}
        onBlur={this.handleBlur}
        onKeyDown={this.handleKeyDown}
      />
    }

    return (<span
      className={this.props.className +
      ' title-input title-input-reading'}
      onClick={this.handleClick}>
			{this.props.preText || 'Now editing '}
      <b>{this.props.title}</b>
        </span>)
  }
}