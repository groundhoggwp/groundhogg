import React from 'react'
import { Modal } from 'react-bootstrap'
import { connect } from 'react-redux'
import { closeVideoModal, openVideoModal } from '../../actions/videoModalActions'

import './style.scss'

const VideoModal = ({ src, title, show, closeVideoModal }) => {

  const handleOnHide = () => {
    closeVideoModal()
  }

  return (
    <Modal
      size="lg"
      aria-labelledby="contained-modal-title-vcenter"
      show={show}
      onHide={handleOnHide}
      centered
      className={'video-modal'}
    >
      <Modal.Header closeButton>
        <Modal.Title id="contained-modal-title-vcenter">
          {title}
        </Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <div className={'embed-responsive embed-responsive-16by9'}>
          <iframe
            className={'embed-responsive-item'}
            src={src} frameBorder="0"
            allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
            allowFullScreen/>
        </div>
      </Modal.Body>
    </Modal>
  )

}

const mapStateToProps = state => ({
  src: state.videoModal.src,
  title: state.videoModal.title,
  show: state.videoModal.show
})

export default connect(mapStateToProps, { closeVideoModal, openVideoModal })(VideoModal)