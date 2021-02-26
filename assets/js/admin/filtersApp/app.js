var filtersApp = Vue.createApp({
  data () {
    return {
      filterGroups: [
        {
          filters: [
            {
              type: 'first_name'
            }
          ]
        }
      ]
    }
  },
  methods : {

    addFilter ( index ) {
      this.filterGroups[ index ].filters.push({

      })
    },

    addFilterGroup () {
      this.filterGroups.push({
        filters: [
          {}
        ]
      })
    }

  },
  computed : {

  }
})