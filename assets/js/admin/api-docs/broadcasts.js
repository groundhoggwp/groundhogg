(()=>{

  const { sprintf, __, _x, _n } = wp.i18n
  const { ApiRegistry, CommonParams, setInRequest, getFromRequest } = Groundhogg.apiDocs
  const { root: apiRoot } = Groundhogg.api.routes.v4

  const {
    Fragment,
    Pg,
    Input,
  } = MakeEl

  ApiRegistry.add('broadcasts', {
    name: __('Broadcasts'),
    description: () => Fragment([
      Pg({}, __('Send or manage broadcasts remotely.', 'groundhogg'))
    ]),
    endpoints: Groundhogg.createRegistry(),
  })

  ApiRegistry.broadcasts.endpoints.add('list', {
    name       : __('List broadcasts', 'groundhogg'),
    description: () => Pg({}, __('List all broadcasts.', 'groundhogg')),
    method     : 'GET',
    endpoint   : `${ apiRoot }/broadcasts`,
    params     : [
      {
        param      : 'object_type',
        type       : 'string',
        required   : false,
        options    : [
          'email',
          'sms',
        ],
        default    : 'email',
        description: () => Fragment([
          Pg({}, __('Specify whether the broadcast is for an email or SMS. If using SMS you must have the SMS addon installed.', 'groundhogg')),
        ]),
      },
      {
        param      : 'object_id',
        type       : 'int',
        required   : false,
        description: () => Fragment([
          Pg({}, __('The ID of the email or SMS used in the broadcast.', 'groundhogg')),
        ]),
      },
      {
        param      : 'scheduled_by',
        type       : 'int',
        required   : false,
        description: () => Fragment([
          Pg({}, __('The user ID of the scheduler.', 'groundhogg')),
        ]),
      },
      {
        param      : 'status',
        type       : 'string',
        options    : [
          'scheduled',
          'pending',
          'sending',
          'sent',
          'cancelled',
        ],
        default    : 'sent',
        required   : true,
        description: () => Fragment([
          Pg({}, __('The status of the broadcasts', 'groundhogg')),
        ]),
      },
      CommonParams.before(),
      CommonParams.after(),
      CommonParams.limit('broadcasts'),
      CommonParams.offset('broadcasts'),
      CommonParams.order('broadcasts'),
      CommonParams.orderby('broadcasts', [
        'ID',
        'send_time',
        'date_scheduled',
      ]),
    ],
    request: {
      object_type: 'email',
      status: 'sent',
      limit: 10,
    },
    response: {
      "total_items": 10,
      "items": [
        {
          "ID": 33,
          "data": {
            "object_id": "30",
            "object_type": "email",
            "scheduled_by": "1",
            "send_time": "1740765600",
            "query": {
              "marketable": true
            },
            "status": "sent",
            "date_scheduled": "2025-02-11 05:23:49",
            "ID": 33
          },
          "meta": {
            "segment_type": "fixed",
            "is_transactional": "",
            "num_scheduled": 10000,
            "total_contacts": 10000,
            "batch_time_elapsed": "0.06",
            "batch_delay": "0",
            "task_id": "16",
            "is_scheduled": "1",
            "cached_report_data": {
              "waiting": 0,
              "id": 33,
              "sent": 11613,
              "email_id": 30,
              "opened": 1,
              "open_rate": 0.01,
              "clicked": 0,
              "all_clicks": 0,
              "click_through_rate": 0,
              "unopened": 11612,
              "opened_not_clicked": 1,
              "unsubscribed": 0
            }
          },
          "object": {
            "ID": 30,
            "data": {
              "content": "...",
              "plain_text": "...",
              "subject": "Testing",
              "title": "Testing",
              "pre_header": "",
              "from_user": 0,
              "author": "1",
              "is_template": "0",
              "status": "draft",
              "message_type": "marketing",
              "last_updated": "2025-10-14 16:34:52",
              "date_created": "2025-10-14 16:34:52",
              "ID": "260",
              "from_select": 0,
              "from_type": "owner"
            },
            "meta": {
              "css": "\n",
              "blocks": "",
              "type": "html",
            },
            "admin": "https://groundhogg.dev/wp-admin/admin.php?page=gh_emails&email=30&action=edit",
            "campaigns": [],
          },
          "date_sent_pretty": "February 28, 2025 1:00 pm"
        }
      ],
      "status": "success"
    },
  })

  ApiRegistry.broadcasts.endpoints.add('read-single', {
    name: __('Retrieve a broadcast', 'groundhogg'),
    description: () => Pg({}, __('Retrieve a single broadcast.', 'groundhogg')),
    method: 'GET',
    endpoint: `${ apiRoot }/broadcasts/:id`,
    identifiers: [
      CommonParams.id('broadcast'),
    ],
    request: {},
    response: {
      "item": {
        "ID": 33,
        "data": {
          "object_id": "30",
          "object_type": "email",
          "scheduled_by": "1",
          "send_time": "1740765600",
          "query": {
            "marketable": true
          },
          "status": "sent",
          "date_scheduled": "2025-02-11 05:23:49",
          "ID": 33
        },
        "meta": {
          "segment_type": "fixed",
          "is_transactional": "",
          "num_scheduled": "11617",
          "total_contacts": "11617",
          "batch_time_elapsed": "0.06",
          "batch_delay": "0",
          "task_id": "16",
          "is_scheduled": "1",
          "cached_report_data": {
            "waiting": 0,
            "id": 33,
            "sent": 11613,
            "email_id": 30,
            "opened": 1,
            "open_rate": 0.01,
            "clicked": 0,
            "all_clicks": 0,
            "click_through_rate": 0,
            "unopened": 11612,
            "opened_not_clicked": 1,
            "unsubscribed": 0
          }
        },
        "object": {
          "ID": 30,
          "data": {
            "content": "...",
            "plain_text": "...",
            "subject": "Testing",
            "title": "Testing",
            "pre_header": "",
            "from_user": 0,
            "author": "1",
            "is_template": "0",
            "status": "draft",
            "message_type": "marketing",
          },
          "meta": {
            "css": "\n",
            "blocks": "",
            "type": "html",
          },
          "campaigns": [],
        },
        "date_sent_pretty": "February 28, 2025 1:00 pm"
      },
      "status": "success"
    },
  })

  ApiRegistry.broadcasts.endpoints.add('read-single-report', {
    name: __('Retrieve broadcast report', 'groundhogg'),
    description: () => Pg({}, __('Retrieve the report of a single broadcast.', 'groundhogg')),
    method: 'GET',
    endpoint: `${ apiRoot }/broadcasts/:id/report`,
    identifiers: [
      CommonParams.id('broadcast'),
    ],
    request: {},
    response: {
      "report": {
        "waiting": 0,
        "id": 33,
        "sent": 11613,
        "email_id": 30,
        "opened": 1,
        "open_rate": 0.01,
        "clicked": 0,
        "all_clicks": 0,
        "click_through_rate": 0,
        "unopened": 11612,
        "opened_not_clicked": 1,
        "unsubscribed": 0
      },
      "status": "success"
    },
  })

  ApiRegistry.broadcasts.endpoints.add('create', {
    name: __('Schedule a broadcast', 'groundhogg'),
    description: () => Pg({}, __('Schedule a new broadcast to be sent.', 'groundhogg')),
    method: 'POST',
    endpoint: `${ apiRoot }/broadcasts/`,
    params: [
      {
        param: 'object_type',
        type: 'string',
        required: false,
        options: ['email', 'sms'],
        default: 'email',
        description: () => Fragment([
          Pg({}, __('Specify whether the broadcast is for an email or SMS. If using SMS you must have the SMS addon installed.', 'groundhogg')),
        ]),
      },
      {
        param: 'object_id',
        type: 'int',
        required: true,
        description: () => Fragment([
          Pg({}, __('The ID of the email or SMS to use in the broadcast.', 'groundhogg')),
        ]),
      },
      {
        param: 'query',
        description: () => Fragment([
          Pg({}, __('The query object is how you select the contacts that will receive the broadcast.', 'groundhogg')),
        ]),
        type: 'object',
        required: true,
        subParams: [
          CommonParams.filters('contacts'),
        ],
      },
      {
        param: 'segment_type',
        type: 'string',
        required: false,
        options: [
          'fixed',
          'dynamic',
        ],
        default: 'fixed',
        description: () => Fragment([
          Pg({}, __('Which type of segment to use.', 'groundhogg')),
        ]),
      },
      {
        param: 'date',
        type: 'string',
        required: true,
        description: () => Fragment([
          Pg({}, __('The date you want to broadcast to be sent. Uses the site timezone.', 'groundhogg')),
          Pg({}, __('Unneeded if using <code>send_now</code>.', 'groundhogg')),
        ]),
        control: ({ param, id }) => Input({
          type: 'date',
          name: param,
          id,
          value: getFromRequest(param),
          onInput: e => setInRequest(param, e.target.value),
        }),
      },
      {
        param: 'time',
        type: 'string',
        required: true,
        description: () => Fragment([
          Pg({}, __('The time of day for broadcast to be sent. Uses the site timezone.', 'groundhogg')),
          Pg({}, __('Unneeded if using <code>send_now</code>.', 'groundhogg')),
        ]),
        control: ({ param, id }) => Input({
          type: 'time',
          name: param,
          id,
          value: getFromRequest(param),
          onInput: e => setInRequest(param, e.target.value),
        }),
      },
      {
        param: 'send_now',
        type: 'bool',
        required: false,
        description: () => Fragment([
          Pg({}, __('Whether to send the broadcast immediately.', 'groundhogg')),
        ]),
      },
      {
        param: 'send_in_local_time',
        type: 'bool',
        required: false,
        description: () => Fragment([
          Pg({}, __('Send based on the contacts\' local time.', 'groundhogg')),
        ]),
      },
      {
        param: 'batching',
        type: 'bool',
        required: false,
        description: () => Fragment([
          Pg({}, __('Whether to send the broadcast in batches.', 'groundhogg')),
        ]),
      },
      {
        param: 'batch_amount',
        type: 'int',
        required: false,
        description: () => Fragment([
          Pg({}, __('The size of the batches.', 'groundhogg')),
        ]),
      },
      {
        param: 'batch_interval',
        type: 'string',
        required: false,
        options: [
          'minutes',
          'hours',
          'days',
        ],
        description: () => Fragment([
          Pg({}, __('The batch interval unit.', 'groundhogg')),
        ]),
      },
      {
        param: 'batch_interval_length',
        type: 'int',
        required: false,
        description: () => Fragment([
          Pg({}, __('The length of the batch interval.', 'groundhogg')),
        ]),
      },
    ],
    request: {
      'query': {
        'filters': [
          [
            {
              'type': 'is_marketable',
              'marketable': 'yes',
            },
          ],
        ],
      },
      object_type: 'email',
      object_id: 1,
      date: moment().format('YYYY-MM-DD'),
      time: moment().add(1, 'hour').format('HH:00:00'),
      send_now: false,
    },
    response: {},
  })

  ApiRegistry.broadcasts.endpoints.add('cancel', {
    name: __('Cancel a broadcast', 'groundhogg'),
    description: () => Pg({}, __('Cancel a broadcast.', 'groundhogg')),
    method: 'POST',
    endpoint: `${ apiRoot }/broadcasts/:id`,
    identifiers: [
      CommonParams.id('broadcast')
    ],
    request: {},
    response: {
      status:'success'
    }
  })
})()
