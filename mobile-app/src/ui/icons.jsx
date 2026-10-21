// Stroke icons (24px grid, Lucide style). Features name an icon by key in the manifest, e.g. 'phone'.
// An unknown key falls back to a dot, so a feature naming an icon core lacks still renders something.

const PATHS = {
  home: 'M3 11.5 12 4l9 7.5M5.5 10v9.5a.5.5 0 0 0 .5.5h4v-6h4v6h4a.5.5 0 0 0 .5-.5V10',
  contacts: 'M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20M10 11.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM20 20v-1.5a3.5 3.5 0 0 0-2.6-3.4M15.5 4.6a3.5 3.5 0 0 1 0 6.8',
  tasks: 'M9 6h11M9 12h11M9 18h11M3.5 6l1.5 1.5L7.5 5M3.5 12l1.5 1.5 2.5-2.5M3.5 18l1.5 1.5 2.5-2.5',
  phone: 'M21 16.5v3a1.5 1.5 0 0 1-1.6 1.5A17.5 17.5 0 0 1 3 4.6 1.5 1.5 0 0 1 4.5 3h3a1.5 1.5 0 0 1 1.5 1.3c.1 1 .4 1.9.7 2.8a1.5 1.5 0 0 1-.3 1.6L8.1 9.9a13.5 13.5 0 0 0 6 6l1.2-1.3a1.5 1.5 0 0 1 1.6-.3c.9.3 1.8.6 2.8.7a1.5 1.5 0 0 1 1.3 1.5Z',
  message: 'M21 12a8 8 0 0 1-11.7 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z',
  mail: 'M4 6h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm0 .5 8 6 8-6',
  note: 'M6 3h9l4 4v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1ZM14 3v5h5M8.5 13h7M8.5 17h5',
  plus: 'M12 5v14M5 12h14',
  search: 'm20 20-3.8-3.8M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z',
  back: 'm14.5 5-7 7 7 7',
  chevron: 'm9.5 5 7 7-7 7',
  check: 'm5 12.5 4.5 4.5L19 7.5',
  more: 'M5 12h.01M12 12h.01M19 12h.01',
  menu: 'M4 7h16M4 12h16M4 17h16',
  close: 'M6 6l12 12M18 6 6 18',
  calendar: 'M7 3v4M17 3v4M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z',
  deals: 'M3 7h18v12H3zM8 7V5a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M3 12.5h18',
  building: 'M5 21V4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v17M15 9h3a1 1 0 0 1 1 1v11M3 21h18M9 7h2M9 11h2M9 15h2',
  external: 'M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5',
  logout: 'M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3M10 16l-4-4 4-4M6 12h10',
  eye: 'M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
  link: 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3A4 4 0 0 0 11 18.7l1-1',
  globe: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18ZM3 12h18M12 3c2.5 2.4 3.8 5.4 3.8 9s-1.3 6.6-3.8 9c-2.5-2.4-3.8-5.4-3.8-9S9.5 5.4 12 3Z',
  alert: 'M12 4 2.5 20h19L12 4ZM12 10v4M12 17h.01',
  form: 'M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1ZM8 6H6a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-2M9 12h6M9 16h4',
  reply: 'M9 14 4 9l5-5M4 9h10a6 6 0 0 1 6 6v3',
  send: 'M21 3 10 14M21 3l-7 18-4-7-7-4 18-7Z',
  dot: 'M12 12h.01',
}

export function Icon ( { name, size, ...props } ) {
  return (
    <svg class="ghm-icon" viewBox="0 0 24 24" aria-hidden="true" width={ size } height={ size } { ...props }>
      <path d={ PATHS[ name ] || PATHS.dot }/>
    </svg>
  )
}

export const iconNames = Object.keys( PATHS )
