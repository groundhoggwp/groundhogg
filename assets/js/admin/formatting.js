(() => {

  const formatTime = (date) => {
    return Intl.DateTimeFormat(Groundhogg.locale, {
      timeStyle: 'short',
      // dateStyle: 'medium'
      // timeZone: 'UTC'
    }).format(new Date(date))
  }

  const formatDateTime = (date, opts) => {
    return Intl.DateTimeFormat(Groundhogg.locale, {
      timeStyle: 'short',
      dateStyle: 'medium',
      ...opts
    }).format(new Date(date))
  }

  const formatDate = (date) => {
    return Intl.DateTimeFormat(Groundhogg.locale, {
      // timeStyle: 'short',
      dateStyle: 'medium',
      timeZone: 'UTC'
    }).format(new Date(date))
  }

  const formatNumber = (num) => {
    return Intl.NumberFormat(Groundhogg.locale, {} ).format(num)
  }

  Groundhogg.formatting = {
    formatTime,
    formatDate,
    formatDateTime,
    formatNumber,
  }
})()
