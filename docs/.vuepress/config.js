module.exports = {
  title: '炸毛框架',
  description: '一个聊天机器人 + Web 框架',
  theme: 'antdocs',
  markdown: {
    lineNumbers: true
  },
  head: [
    ['link', { rel: 'icon', href: '/logo_trans.png' }],
    ['script', {}, `
        var _hmt = _hmt || [];
(function () {
    var hm = document.createElement("script");
    hm.src = "https://hm.baidu.com/hm.js?f0f276cefa10aa31a20ae3815a50b795";
    var s = document.getElementsByTagName("script")[0];
    s.parentNode.insertBefore(hm, s);
})();
    `]
  ],
  themeConfig: {
    repo: 'zhamao-robot/zhamao-framework',
    logo: '/logo_trans.png',
    docsDir: 'docs',
    editLinks: true,
    lastUpdated: '上次更新',
    activeHeaderLinks: false,
    nav: [
      { text: '指南', link: '/guide/' },
      { text: '事件和注解', link: '/event/' },
      { text: '组件', link: '/component/' },
      { text: '进阶', link: '/advanced/' },
      { text: 'API', link: '/api/' },
      { text: 'FAQ', link: '/faq/' },
      { text: '更新日志', link: '/update/v2/' },
      { text: '炸毛框架 v1', link: 'https://docs-v1.zhamao.xin/' }
    ],
    sidebar: {
      '/guide/': [
        {
          title: '指南',
          collapsable: false,
          sidebarDepth: 1,
          children: [
            '',
            'installation',
            'quickstart-robot',
            'quickstart-http',
            'onebot-choose',
            'basic-config',
            'write-module',
            'register-event',
            'upgrade',
            'errcode'
          ]
        }
      ],
      '/event/': [
        {
          title: '事件和注解',
          collapsable: false,
          sidebarDepth: 1,
          children: [
            '',
            'robot-annotations',
            'route-annotations',
            'framework-annotations',
            'middleware',
            'custom-annotations',
            'event-dispatcher'
          ]
        }
      ],
      '/component/': [
        '',
        {
          title: '聊天机器人组件',
          collapsable: true,
          sidebarDepth: 2,
          children: [
            'bot/robot-api-12',
            'bot/robot-api',
            'bot/cqcode',
            'bot/message-util',
            'bot/access-token',
            'bot/turing-api',
            'bot/help-generator.md',
          ]
        },
        {
          title: '存储组件',
          collapsable: true,
          sidebarDepth: 2,
          children: [
            'store/light-cache',
            'store/mysql',
            'store/mysql-db',
            'store/redis',
            'store/atomics',
            'store/spin-lock',
            'store/data-provider'
          ]
        },
        {
          title: '通用组件',
          collapsable: true,
          sidebarDepth: 2,
          children: [
            'common/context',
            'common/coroutine-pool',
            'common/singleton-trait',
            'common/zmutil',
            'common/global-functions',
            'common/console',
            'common/task-worker',
            'common/remote-terminal',
            'common/event-tracer'
          ]
        },
        {
          title: 'HTTP 组件',
          collapsable: true,
          sidebarDepth: 2,
          children: [
            'http/zmrequest',
            'http/route-manager'
          ]
        },
        {
          title: '模块/插件管理',
          collapsable: true,
          sidebarDepth: 2,
          children: [
            'module/module-pack',
            'module/module-unpack'
          ]
        }
      ],
      '/advanced/': [
        '',
        {
          title: '框架高级开发',
          collapsable: true,
          sidebarDepth: 2,
          children: [
            'framework-structure',
            'custom-start',
            'manually-install',
            'inside-class',
            'multi-process',
            'task-worker'
          ]
        },
        {
          title: '开发实战教程',
          collapsable: true,
          sidebarDepth: 2,
          children: [
            'connect-ws-client',
            'example/admin',
            'example/integrate-qingyunke-chatbot',
            'example/weather-bot'
          ]
        },
      ],
      '/api/': require('./api.js'),
      '/faq/': [
        '',
        'to-v2',
        'usual-question',
        'address-already-in-use',
        'display-deadlock',
        'light-cache-wrong',
        'wait-message-cqbefore'
      ],
      '/update/': [
        {
          title: '更新日志',
          collapsable: true,
          sidebarDepth: 0,
          children: [
            'v2',
            'v1',
            'build-update'
          ]
        },
        'config'
      ]
    }
  }
}