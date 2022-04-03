<template>
<div class="doc-chat-container">
  <div class="doc-chat-content">
    <div v-for="i in chat" v-bind="i">
      <div class="doc-chat-row" v-if="i.type === 0">
        <div class="doc-chat-box">{{ i.content }}</div>
        <img class="doc-chat-avatar" src="https://api.btstu.cn/sjtx/api.php"  alt=""/>
      </div>
      <div class="doc-chat-row doc-chat-row-robot" v-else-if="i.type === 1">
        <img class="doc-chat-avatar" src="https://docs-v1.zhamao.xin/logo.png" alt=""/>
        <div class="doc-chat-box doc-chat-box-robot">
          <span v-for="(p,index) in i.content.split('\n')">{{p}}<br v-if="index !== i.content.length - 1"></span>
        </div>
      </div>
      <div class="doc-chat-row doc-chat-banner" v-else-if="i.type === 2">
        {{ i.content }}
      </div>
      <div class="doc-chat-row doc-chat-row-robot" v-else-if="i.type === 3">
        <img class="doc-chat-avatar" src="https://docs-v1.zhamao.xin/logo.png" alt=""/>
        <div class="doc-chat-box doc-chat-box-robot">
          <img :src="i.content" alt="" />
        </div>
      </div>
    </div>
  </div>
</div>
</template>

<!--
type:
  0: 我方发送消息
  1: 机器人回复消息
  2: 显示一个横幅系统消息
  3: 机器人回复一个图片
-->

<script>
export default {
  name: "ChatBox",
  props: ['myChats'],
  data() {
    return {
      chat: this.myChats,
      multiline: ''
    }
  }
}
</script>

<style scoped>
.doc-chat-content {
  padding: 12px;
}
.doc-chat-container {
  border-radius: 6px;
  max-width: 550px;
  min-height: 30px;
  /*noinspection CssUnresolvedCustomProperty*/
  background-color: #f2f4f5;

  margin-bottom: 1em;
  box-shadow: 0 3px 1px -2px rgba(0,0,0,.2), 0 2px 2px 0 rgba(0,0,0,.14), 0 1px 5px 0 rgba(0,0,0,.12);
}

.doc-chat-row {
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  flex: 1 1 auto;
  justify-content: flex-end;
}

.doc-chat-banner {
  justify-content: center;
  background: rgba(0,0,0,0.1);
  width: max-content;
  margin: 8px auto;
  padding: 4px 14px;
  border-radius: 8px;
  color: gray;
  font-size: 14px;
}

.doc-chat-row-robot {
  justify-content: flex-start !important;
}

.doc-chat-box {
  color: #000000de;
  position: relative;
  width: fit-content;
  max-width: 55%;
  border-radius: .5rem;
  padding: .4rem .6rem;
  margin: .4rem .8rem;
  background-color: #fff;
  line-height: 1.5;
  font-size: 16px;
  outline: none;
  overflow-wrap: break-word;
  white-space: normal;
  box-shadow: 0 2px 12px 0 rgba(0,0,0,.1);
}

.doc-chat-box:after {
  content: "";
  position: absolute;
  right: auto;
  top: 0;
  width: 8px;
  height: 12px;
  color: #fff;
  border: 0 solid transparent;
  border-bottom: 7px solid;
  border-radius: 0 0 8px 0;
  left: calc(100% - 4px);
  box-sizing: inherit;
}

.doc-chat-box-robot:after {
  content: "";
  position: absolute;
  right: calc(100% - 4px);
  top: 0;
  width: 8px;
  height: 12px;
  color: #fff;
  border: 0 solid transparent;
  border-bottom: 7px solid;
  border-radius: 0 0 0 8px;
  left: auto;
  box-sizing: inherit;
}

.doc-chat-avatar {
  background-color: aquamarine;
  width: 36px !important;
  height: 36px !important;
  border-radius: 18px;
}
</style>
