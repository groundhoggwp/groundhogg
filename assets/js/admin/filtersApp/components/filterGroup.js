filtersApp.component('filter-group', {
  props: {
    id: {
      type: Number,
      required: true
    },
    filters: {
      type: Array,
      required: true
    }
  },
  template:
    `<div class="filter-group-wrap">
        <div class="filter-and-group" v-for="(filter, kIndex) in filters" :key="kIndex">
          <filter :filter="filter" @update-filter="updateFilter"></filter>
        </div>
        <button class="button" type="button"
                v-on:click="addFilter">Add Filter
        </button>
      </div>`,
  methods: {
    addFilter () {
      this.$emit('add-filter', this.id)
    },
    updateFilter (filterIndex, filterArgs) {
      this.$emit('update-filter', filterIndex, filterArgs )
    }
  }
})