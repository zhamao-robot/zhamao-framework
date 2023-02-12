<template>
  <a-row>
    <a-col :span="8" v-for="item in plugin_list" v-bind="item" >
      <a :href="item.link">
        <a-card hoverable style="width: 280px; margin: 8px; height: 120px" >
          <a-card-meta :title="item.name" :description="item.description">
            <template #avatar>
              <a-avatar :src="item.avatar == null ? 'https://zhamao.xin/file/hello.jpg' : item.avatar" />
            </template>
          </a-card-meta>
        </a-card>
      </a>
    </a-col>
  </a-row>
</template>

<script>
export default {
  name: "PluginList",
  data() {
    return {
      plugin_list: []
    }
  },
  created() {
    this.plugin_list = require('./plugin_list');
    console.log(this.plugin_list);
  },
  methods: {
    jumpTo(link) {
      window.location.href = link.link;
      return true;
    }
  }
}
</script>

<style scoped>
.ant-avatar {
  width: 36px;
  height: 36px;
}
</style>
