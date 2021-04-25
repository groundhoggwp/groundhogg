import { __ } from '@wordpress/i18n';
import {
  // Avatar,
  // Box,
  Chip,
  // Divider,
  // Drawer,
  // Hidden,
  // Link,
  // List,
  // ListSubheader,
  // Typography,
  // makeStyles
} from '@material-ui/core';
import ReceiptIcon from '@material-ui/icons/ReceiptOutlined';
import {
  Briefcase as BriefcaseIcon,
  Calendar as CalendarIcon,
  ShoppingCart as ShoppingCartIcon,
  Folder as FolderIcon,
  BarChart as BarChartIcon,
  Lock as LockIcon,
  UserPlus as UserPlusIcon,
  AlertCircle as AlertCircleIcon,
  Trello as TrelloIcon,
  User as UserIcon,
  Layout as LayoutIcon,
  Edit as EditIcon,
  DollarSign as DollarSignIcon,
  Mail as MailIcon,
  MessageCircle as MessageCircleIcon,
  PieChart as PieChartIcon,
  Share2 as ShareIcon,
  Users as UsersIcon
} from 'react-feather';
import DashboardIcon from '@material-ui/icons/Dashboard';
import PeopleIcon from '@material-ui/icons/People';
// import BarChartIcon from '@material-ui/icons/BarChart';
import SettingsIcon from '@material-ui/icons/Settings';
import EmailIcon from '@material-ui/icons/Email';
import LinearScaleIcon from '@material-ui/icons/LinearScale';
import LocalOfferIcon from '@material-ui/icons/LocalOffer';
import SettingsInputAntennaSharpIcon from '@material-ui/icons/SettingsInputAntennaSharp';
import BuildIcon from '@material-ui/icons/Build';
import {
	Dashboard,
	// Reports,
	Emails,
	Tags,
	Contacts,
	Funnels,
	Settings,
	Broadcasts,
	Tools
} from './pages'

const navSections = [
  {
    subheader: 'Pages',
    items: [
      {
        title: 'Dashboard',
        component: Dashboard,
        icon: PieChartIcon,
        href: '/',
        path: '/',
        link: '/',
    	},
      // {
      //   title: 'Reports',
      //   component: Reports,
      //   icon: BarChartIcon,
      //   href: '/reports/:routeId',
      //   path: '/reports/:routeId',
      //   link: '/reports/:routeId'
    	// },
      {
        title: 'Broadcasts',
        component: Broadcasts,
        icon: SettingsInputAntennaSharpIcon,
        href: '/broadcasts',
        path: '/broadcasts',
        link: '/broadcasts',
    	},
      {
        title: 'Contacts',
        component: Contacts,
        icon: PeopleIcon,
        href: '/contacts',
        path: '/contacts',
        link: '/contacts'
    	},
      {
        title: 'Tags',
        component: Tags,
        icon: LocalOfferIcon,
        href: '/tags',
        path: '/tags',
        link: '/tags'
    	},
      {
        title: 'Email',
        component: Emails,
        icon: EmailIcon,
        href: '/emails',
        path: '/emails',
        link: '/emails'
    	},
      {
        title: 'Funnels',
        component: Funnels,
        icon: LinearScaleIcon,
        href: '/funnels',
        path: '/funnels',
        link: '/funnels',
    	},
      {
        title: 'Tools',
        component: Settings,
        icon: BuildIcon,
        href: '/tools',
        path: '/tools',
        link: '/tools',
    	},
    ]
  },
  {
    subheader: 'Reports',
    items: [
      {
        title: 'Dashboard',
        icon: PieChartIcon,
        href: '/app/reports/dashboard'
      },
      {
        title: 'Dashboard Alternative',
        icon: BarChartIcon,
        href: '/app/reports/dashboard-alternative'
      }
    ]
  },
  {
    subheader: 'Management',
    items: [
      {
        title: 'Customers',
        icon: UsersIcon,
        href: '/app/management/customers',
        items: [
          {
            title: 'List Customers',
            href: '/app/management/customers'
          },
          {
            title: 'View Customer',
            href: '/app/management/customers/1'
          },
          {
            title: 'Edit Customer',
            href: '/app/management/customers/1/edit'
          }
        ]
      },
      {
        title: 'Products',
        icon: ShoppingCartIcon,
        href: '/app/management/products',
        items: [
          {
            title: 'List Products',
            href: '/app/management/products'
          },
          {
            title: 'Create Product',
            href: '/app/management/products/create'
          }
        ]
      },
      {
        title: 'Orders',
        icon: FolderIcon,
        href: '/app/management/orders',
        items: [
          {
            title: 'List Orders',
            href: '/app/management/orders'
          },
          {
            title: 'View Order',
            href: '/app/management/orders/1'
          }
        ]
      },
      {
        title: 'Invoices',
        icon: ReceiptIcon,
        href: '/app/management/invoices',
        items: [
          {
            title: 'List Invoices',
            href: '/app/management/invoices'
          },
          {
            title: 'View Invoice',
            href: '/app/management/invoices/1'
          }
        ]
      }
    ]
  },
  {
    subheader: 'Applications',
    items: [
      {
        title: 'Projects Platform',
        href: '/app/projects',
        icon: BriefcaseIcon,
        items: [
          {
            title: 'Overview',
            href: '/app/projects/overview'
          },
          {
            title: 'Browse Projects',
            href: '/app/projects/browse'
          },
          {
            title: 'Create Project',
            href: '/app/projects/create'
          },
          {
            title: 'View Project',
            href: '/app/projects/1'
          }
        ]
      },
      {
        title: 'Social Platform',
        href: '/app/social',
        icon: ShareIcon,
        items: [
          {
            title: 'Profile',
            href: '/app/social/profile'
          },
          {
            title: 'Feed',
            href: '/app/social/feed'
          }
        ]
      },
      {
        title: 'Kanban',
        href: '/app/kanban',
        icon: TrelloIcon
      },
      {
        title: 'Mail',
        href: '/app/mail',
        icon: MailIcon
      },
      {
        title: 'Chat',
        href: '/app/chat',
        icon: MessageCircleIcon,
        info: () => (
          <Chip
            color="secondary"
            size="small"
            label="Updated"
          />
        )
      },
      {
        title: 'Calendar',
        href: '/app/calendar',
        icon: CalendarIcon,
        info: () => (
          <Chip
            color="secondary"
            size="small"
            label="Updated"
          />
        )
      }
    ]
  },
  {
    subheader: 'Auth',
    items: [
      {
        title: 'Login',
        href: '/login-unprotected',
        icon: LockIcon
      },
      {
        title: 'Register',
        href: '/register-unprotected',
        icon: UserPlusIcon
      }
    ]
  },
  {
    subheader: 'Extra',
    items: [
      {
        title: 'Charts',
        href: '/app/extra/charts',
        icon: BarChartIcon,
        items: [
          {
            title: 'Apex Charts',
            href: '/app/extra/charts/apex'
          }
        ]
      },
      {
        title: 'Forms',
        href: '/app/extra/forms',
        icon: EditIcon,
        items: [
          {
            title: 'Formik',
            href: '/app/extra/forms/formik'
          },
          {
            title: 'Redux Forms',
            href: '/app/extra/forms/redux'
          },
        ]
      },
      {
        title: 'Editors',
        href: '/app/extra/editors',
        icon: LayoutIcon,
        items: [
          {
            title: 'DraftJS Editor',
            href: '/app/extra/editors/draft-js'
          },
          {
            title: 'Quill Editor',
            href: '/app/extra/editors/quill'
          }
        ]
      }
    ]
  }
];

export default navSections;
