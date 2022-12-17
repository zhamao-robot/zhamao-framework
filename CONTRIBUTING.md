# 炸毛框架贡献指南

首先很高兴你愿意读这篇文章，因为我们需要更多的开发者来帮助我们开发和维护。

如果你还没加入我们的 [QQ 群（670821194）][qq-group]，我们建议你加入一下，以便和其他开发者实时交流和协作。

## 测试

我们目前采用 PHPUnit 作为测试框架，如果你编写了新的功能或改变了现有的行为，我们希望你可以编写相应的测试代码。

## 提交变更

请使用 [GitHub Pull Request][new-pr] 提交你的更改，并附上恰当的说明（例如一个简明的列表列出所作的改动）。

同时，请不要忘记在提交前执行代码格式检查 `composer cs-fix` 和静态分析检查 `composer analyse`
。虽然我们的自动化流程会在你推送到仓库后自动运行检查，但我仍然建议你提前在本地运行，以节省可能的修改成本。

对了，请尽量为你的每个函数、方法加上 PHPDoc 注释。

## 具体贡献流程

#### **你发现了个 BUG？**

- **如果你发现的是安全漏洞，请不要在 GitHub 上回报！** 请直接联系 [@crazywhalecc](https://github.com/crazywhalecc)。

- **确保该问题还没被其他人回报。** 你可以在 [Issue 列表][issues] 中搜索相关关键词来检查。

- 如果你没能找到一个已有的 Issue 来描述你遇到的问题，[请新建一个 Issue][new-issue]。请务必包含**标题和清晰的描述**
  ，尽可能多的相关信息，以及一个**代码示例**或者一个**可执行的测试用例**，来描述你遇到的问题。

- 如果可以的话，请使用相关的 Issue 模板来创建 Issue。

#### **你修复了个 BUG？**

- 在 GitHub 上提交一个 Pull Request。

- 确保 PR 的描述清楚地描述了问题和解决方案。如果有相关的 Issue，请在 PR 描述中包含 Issue 编号。

#### **你想添加新功能或改变现有功能？**

- 虽然你可以直接提交 PR，但我们建议你先与我们的开发者讨论你的想法，以避免你的努力白费。你可以在 [QQ 群][qq-group]
  中与我们的开发者交流，或者在 [GitHub Issue][issues] 中提出你的想法。

- 如果你的想法被采纳，你可以在 GitHub 上[提交一个 Pull Request][new-pr]。

#### **你对源码有疑问？**

- 你可以在 [QQ 群][qq-group] 中与我们的开发者讨论，或者你也可以在 [GitHub Issue][issues] 中提出你的疑问。

#### **你想为文档做贡献？**

- 非常欢迎^^

- 你可以直接[提交一个 Pull Request][new-pr]。

炸毛框架是由志愿开发者共同维护的项目。我们欢迎你加入我们的开发。

感谢~ :heart: :heart: :heart:

炸毛框架开发团队

[issues]: https://github.com/zhamao-robot/zhamao-framework/issues

[new-pr]: https://github.com/zhamao-robot/zhamao-framework/pull/new/master

[new-issue]: https://github.com/zhamao-robot/zhamao-framework/issues/new

[qq-group]: https://jq.qq.com/?_wv=1027&k=KdIGy0UK
