(() => {

  const {
    ApiRegistry,
    CommonParams,
    setInRequest,
    getFromRequest,
    addBaseObjectCRUDEndpoints,
    currEndpoint,
    currRoute,
  } = Groundhogg.apiDocs
  const { root: apiRoot } = Groundhogg.api.routes.v4
  const { sprintf, __, _x, _n } = wp.i18n

  const {
    Fragment,
    Pg,
    Input,
    Textarea,
    InputRepeater,
  } = MakeEl

  ApiRegistry.add('tasks', {
    name: __('Tasks'),
    description: () => Fragment([
      Pg({}, __('Add or manage tasks for contacts, or other types.', 'groundhogg'))
    ]),
    endpoints: Groundhogg.createRegistry(),
  })

  addBaseObjectCRUDEndpoints(ApiRegistry.tasks.endpoints, {
    plural: __('tasks'),
    singular: __('task'),
    route: `${apiRoot}/tasks`,
    searchableColumns: [
      'content',
      'summary',
    ],
    orderByColumns: [
      'ID',
      'object_id',
      'object_type',
      'date_created',
      'timestamp',
      'user_id',
      'type',
    ],
    readParams: [
      {
        param: 'object_type',
        type: 'string',
        description: __( 'Fetch tasks belonging to a specific object type.', 'groundhogg' ),
        default: 'contact'
      },
      {
        param: 'user_id',
        type: 'int',
        description: __( 'Fetch tasks assigned to a specific user.', 'groundhogg' ),
      },
      {
        param: 'type',
        type: 'string',
        description: __( 'Filter by the type of tasks.', 'groundhogg' ),
        options: [
          'task',
          'call',
          'email',
          'meeting',
        ]
      },
      {
        param: 'incomplete',
        type: 'bool',
        description: __( 'Fetch only incomplete tasks', 'groundhogg' ),
      },
      {
        param: 'complete',
        type: 'bool',
        description: __( 'Fetch only complete tasks.', 'groundhogg' ),
      },
      {
        param: 'mine',
        type: 'bool',
        description: __( 'Fetch tasks belonging to the current user.', 'groundhogg' ),
      }
    ],
    dataParams: [
      {
        param: 'object_id',
        description: __('The ID of the object to associate with the note.', 'groundhogg'),
        type: 'int',
        required: true,
      },
      {
        param: 'object_type',
        description: __('The type of the object to associate with the note.', 'groundhogg'),
        type: 'string',
        default: 'contact',
        required: true,
      },
      {
        param: 'summary',
        description: __('A sentence describing the task.', 'groundhogg'),
        type: 'string',
        required: true,
      },
      {
        param: 'content',
        description: __('The HTML content of the task.', 'groundhogg'),
        type: 'string',
        required: true,
      },
      {
        param: 'type',
        description: __( 'The type of task it is.', 'groundhogg' ),
        type: 'string',
        default: 'note',
        options: [
          'task',
          'call',
          'email',
          'meeting',
        ]
      },
      {
        param: 'due_date',
        description: __( 'When the task is due. Uses the site local time.', 'groundhogg' ),
        type: 'string',
        control: ({ param, id }) => Input({
          type: 'date',
          name: param,
          id,
          value: getFromRequest(param),
          onInput: e => setInRequest(param, e.target.value),
        }),
      },
    ],
    exampleItem: {
      "ID": "1",
      "data": {
        "object_id": "1234",
        "object_type": "contact",
        "step_id": "0",
        "funnel_id": "0",
        "user_id": "1",
        "context": "user",
        "type": "task",
        "summary": "Call this contact",
        "content": "<p>Call this contact and ask them if they need our services or not.</p>",
        "timestamp": "1717696104",
        "date_created": "2024-06-06 17:48:24",
        "date_completed": "0000-00-00 00:00:00",
        "due_date": "2024-06-30 00:00:00",
        "ID": "1"
      },
      "admin": `${Groundhogg.url.admin}admin.php?page=gh_tasks&task=1&action=edit`,
      "i18n": {
        "time_diff": "8 seconds",
        "due_in": "3 weeks",
        "completed": "2026 years",
        "due_date": "June 30, 2024 12:00 am",
        "completed_date": ""
      },
      "is_overdue": false,
      "is_complete": false,
      "due_timestamp": 1719720000,
      "esc_html": {
        "summary": "Call this contact"
      },
      "associated": {
        "link": `${Groundhogg.url.admin}admin.php?page=gh_contacts&contact=1234&action=edit&_tab=tasks`,
        "name": "John Doe",
        "type": "contact"
      }
    }
  })

})()
