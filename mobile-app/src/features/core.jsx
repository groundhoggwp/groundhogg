// Core's own implementation of the 'core' feature. It registers through the same public API an add-on uses.

import { HomeView, MyTasksCard, RecentContactsCard } from '../views/home.jsx'
import { ContactsView, ContactView } from '../views/contacts.jsx'
import { TasksView, NewTaskSheet } from '../views/tasks.jsx'
import { NotesPanel, ContactTasksPanel, NewContactSheet } from '../views/panels.jsx'
import { openEmail } from '../views/email.jsx'

export function registerCore () {
  return window.GroundhoggMobile.register( 'core', {
    apiVersion: 1,
    routes: {
      '/': HomeView,
      '/contacts': ContactsView,
      '/contacts/:id': { component: ContactView, title: 'Contact' },
      '/tasks': TasksView,
    },
    homeCards: {
      'my-tasks': MyTasksCard,
      'recent-contacts': RecentContactsCard,
    },
    contactActions: {
      email: openEmail,
    },
    contactPanels: {
      notes: NotesPanel,
      tasks: ContactTasksPanel,
    },
    quickCreate: {
      'new-contact': NewContactSheet,
      'new-task': NewTaskSheet,
    },
  } )
}
