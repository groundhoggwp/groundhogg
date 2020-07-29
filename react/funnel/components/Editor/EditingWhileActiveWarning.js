import React, { useState } from 'react'
import { Alert } from 'react-bootstrap'

const { __, _x, _n, _nx } = wp.i18n

export function EditingWhileActiveWarning() {
  const [show, setShow] = useState(true);

  if (show) {
    return (
      <Alert variant="warning" onClose={() => setShow(false)} dismissible>
        <p>
          { __( 'Making changes while the funnel is active is not recommended. Deactivate the funnel first, then make your changes.', 'groundhogg' ) }
        </p>
      </Alert>
    );
  }
  return <></>;
}