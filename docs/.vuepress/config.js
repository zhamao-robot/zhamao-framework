module.exports = {
    title: '炸毛框架 v3',
    description: '一个高性能聊天机器人 + Web 框架',
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
        docsBranch: 'main',
        logo: '/logo_trans.png',
        docsDir: 'docs',
        editLinks: true,
        lastUpdated: '上次更新',
        activeHeaderLinks: false,
        nav: [
            { text: '指南', link: '/guide/' },
            { text: '事件', link: '/event/' },
            { text: '组件', link: '/components/bot/bot-context' },
            { text: '插件', link: '/plugins/management' },
            { text: '进阶', link: '/advanced/' },
            { text: '更新日志', link: '/update/v3' },
            { text: 'API 文档', link: '/doxy/', target: '_blank' },
            { text: '炸毛框架 v2', link: 'https://docs-v2.zhamao.xin/' }
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
                        'configuration',
                        'structure',
                        'get-started',
                        'debugging',
                    ]
                }
            ],
            '/event/': [
                {
                    title: '事件',
                    collapsable: false,
                    sidebarDepth: 1,
                    children: [
                        '',
                        'bot',
                        'http',
                        'middleware',
                        'framework',
                        'extend',
                    ]
                }
            ],
            '/components/': [
                '',
                {
                    title: '机器人组件',
                    collapsable: true,
                    sidebarDepth: 2,
                    children: [
                        'bot/bot-context',
                        'bot/message-segment',
                        'bot/authorization',
                    ]
                },
                {
                    title: 'HTTP 组件',
                    collapsable: true,
                    sidebarDepth: 1,
                    children: [
                        'http/zmrequest',
                        'http/websocket-access',
                        'http/websocket-client',
                    ],
                },
                {
                    title: '框架通用组件',
                    collapsable: true,
                    sidebarDepth: 2,
                    children: [
                        'common/class-alias',
                        'common/global-defines',
                        'common/logging',
                        'common/hot-update',
                    ]
                },
                {
                    title: '存储组件',
                    collapsable: true,
                    sidebarDepth: 1,
                    children: [
                        'store/file-system',
                        'store/cache',
                        'store/redis',
                        'store/mysql',
                    ]
                },
                {
                    title: '依赖注入组件',
                    collapsable: true,
                    sidebarDepth: 2,
                    children: [
                        'container/dependency-injection',
                        'container/configuration',
                        'container/dependencies',
                    ],
                }
            ],
            '/plugins/': [
                {
                    title: '插件系统',
                    collapsable: false,
                    sidebarDepth: 2,
                    children: [
                        'management',
                        'develop',
                        'market',
                    ]
                },
            ],
            '/advanced/': [
                {
                    title: '进阶',
                    collapsable: false,
                    sidebarDepth: 1,
                    children: [
                        '',
                        'multi-process',
                        'custom-start',
                    ]
                }
            ],
            '/update/': [
                {
                    title: '更新日志',
                    collapsable: true,
                    sidebarDepth: 0,
                    children: [
                        'v3',
                        'v2',
                    ]
                }
            ]
        }
    }
}
