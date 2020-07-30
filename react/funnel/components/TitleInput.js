import React, { useEffect, useRef, useState } from 'react'

export const TitleInput = ({ title, onChange, className, preText }) => {

  const [editing, setEditing] = useState(false)
  const [tempTitle, setTempTitle] = useState(title)
  const titleInputEl = useRef(null)

  const handleChange = (e) => {
    setTempTitle(e.target.value)
  }

  const handleBlur = (e) => {
    setEditing(false)
    onChange(tempTitle)
  }

  const handleKeyDown = (e) => {
    switch (e.keyCode) {
      case 13:
        handleBlur(e)
        break
      case 27:
        setTempTitle(title)
        setEditing(false)
        break
    }
  }

  const handleClick = (e) => {
    setEditing(true)
  }

  useEffect(() => {
    if ( editing ){
      titleInputEl.current.focus()
    }
  }, [editing])

  return ( <>
    { ! editing && <span
      className={ className +
      ' title-input title-input-reading' }
      onClick={ handleClick }>
    { preText || 'Now editing ' }
      <b>{ title }</b>
    </span> }
    { editing && <input
      type={ 'text' }
      ref={ titleInputEl }
      className={ className +
      ' title-input title-input-editing' }
      value={ tempTitle }
      onChange={ handleChange }
      onBlur={ handleBlur }
      onKeyDown={ handleKeyDown }
    /> }
  </> )

}