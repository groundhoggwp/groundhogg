import React from 'react'
import './style.scss'
import { openVideoModal } from '../../actions/videoModalActions'
import { connect } from 'react-redux'
import { Dashicon } from '../../funnel/components/Dashicon/Dashicon'
import { FaIcon } from '../../components/basic-components'
import Card from 'react-bootstrap/Card'
import Button from 'react-bootstrap/Button'

const { user, assets } = groundhogg

const welcomeVideos = [
  {
    title: 'Import Your Contacts',
    description: 'Switching from another provider? Import your contacts into Groundhogg.',
    link: 'https://www.youtube.com/embed/BmTmVAoWSb0',
    img: assets.welcome.import
  },
  {
    title: 'Configure Cron',
    description: 'Configure the Cron Job so emails send and funnels can run.',
    link: 'https://www.youtube.com/embed/1-csY3W-WP0',
    img: assets.welcome.cron
  },
  {
    title: 'Create Your First Funnel',
    description: 'Launch your first funnel in 1 hour or less and start growing your list.',
    link: 'https://www.youtube.com/embed/W1dwQrqEPVw',
    img: assets.welcome.funnel
  }
]

const courseVideos = [
  {
    title: 'Official Quickstart Course for Beginners',
    details: <><FaIcon classes={['clock-o']}/> {'1 hour'}</>,
    description: 'Taking this course you will be able to quickly build and launch your first Lead Magnet Download funnel with Groundhogg.',
    link: 'https://www.youtube.com/embed/BmTmVAoWSb0',
    img: assets.welcome.course
  },
  {
    title: 'Official Quickstart Course for Beginners',
    details: <><FaIcon classes={['clock-o']}/> {'1 hour'}</>,
    description: 'Taking this course you will be able to quickly build and launch your first Lead Magnet Download funnel with Groundhogg.',
    link: 'https://www.youtube.com/embed/BmTmVAoWSb0',
    img: assets.welcome.course
  }
]

const menuLinks = [
  {
    link: 'https://www.groundhogg.io/',
    name: 'Groundhogg.io',
    icon: 'home'
  },
  {
    link: 'https://help.groundhogg.io/',
    name: 'Documentation',
    icon: 'book'
  },
  {
    link: 'https://groundhogg.io/downloads/',
    name: 'Store',
    icon: 'shopping-cart'
  },
  {
    link: 'https://academy.groundhogg.io/courses/',
    name: 'Courses',
    icon: 'graduation-cap'
  },
  {
    link: 'https://academy.groundhogg.io/courses/',
    name: 'Support Group',
    icon: 'graduation-cap'
  },
  {
    link: 'https://academy.groundhogg.io/courses/',
    name: 'My Account',
    icon: 'graduation-cap'
  },
  {
    link: 'https://academy.groundhogg.io/courses/',
    name: 'Find A Partner',
    icon: 'graduation-cap'
  }
]

const CourseCard = ({ title, description, details, link, img }) => {

  return (
    <Card className={'video-card'}>
      <Card.Img variant="top" src={img} />
      <Card.Body>
        <Card.Title>{title}</Card.Title>
        <Card.Text>
          <div className={'course-details'}>{details}</div>
          {description}
        </Card.Text>
      </Card.Body>
    </Card>
  )
}

const VideoCard = ({ title, description, link, img, openVideoModal }) => {

  const handleOnClick = (e) => {
    openVideoModal(link, title)
  }

  return (
    <Card onClick={handleOnClick} className={'video-card'}>
      <Card.Img variant="top" src={img} />
      <Card.Body>
        <Card.Title>{title}</Card.Title>
        <Card.Text>
          {description}
        </Card.Text>
      </Card.Body>
    </Card>
  )
}

const MenuItem = ({ link, name, icon }) => {
  return (
    <li className={'menu-item'}>
      <Button href={link} variant={'light'} target={'_blank'}>
        <FaIcon classes={[icon]}/> <span className={'menu-item-text'}>{name}</span>
      </Button>
    </li>
  )
}

const VideoCardConnected = connect(null, { openVideoModal })(VideoCard)

export default {
  path: '/',
  icon: 'lightbulb-o',
  title: 'Getting Started',
  capabilities: [],
  exact: true,
  render: () => (
    <div className={'getting-started'}>
      <section className={'welcome-message'}>
        <h1>{'Welcome to Groundhogg,'} {user.data.display_name}.</h1>
      </section>
      <section className={'welcome-section'}>
        <nav className={'welcome-nav'}>
          <ul className={'nav'}>
            {menuLinks.map(item => <MenuItem {...item}/>)}
          </ul>
        </nav>
      </section>
      <section className={'welcome-section welcome-videos'}>
        {welcomeVideos.map(item => <VideoCardConnected {...item}/>)}
      </section>
      <section className={'welcome-section  welcome-videos'}>
        {courseVideos.map(item => <CourseCard {...item}/>)}
      </section>
    </div>
  )
}