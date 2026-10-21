// A contact's opt-in status is a number (Groundhogg\Preferences). The names are translated in the view, so they
// are extracted with the rest of the strings. What belongs here is how serious each status is.

export const UNCONFIRMED = 1
export const CONFIRMED = 2
export const UNSUBSCRIBED = 3
export const WEEKLY = 4
export const MONTHLY = 5
export const HARD_BOUNCE = 6
export const SPAM = 7
export const COMPLAINED = 8
export const BLOCKED = 9

/**
 * The pill color for a status: green when they can be emailed, amber while unconfirmed, red when they can't.
 *
 * @param {number|string} status
 * @returns {'green'|'amber'|'red'|''} empty for a status this app doesn't know
 */
export function optinColor ( status ) {
  switch ( Number( status ) ) {
    case CONFIRMED:
    case WEEKLY:
    case MONTHLY:
      return 'green'
    case UNCONFIRMED:
      return 'amber'
    case UNSUBSCRIBED:
    case HARD_BOUNCE:
    case SPAM:
    case COMPLAINED:
    case BLOCKED:
      return 'red'
    default:
      return ''
  }
}
