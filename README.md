# Dream 2.0 MXIN

## 项目信息

Dream 2.0 MXIN 是基于
[Dream（nineya/halo-theme-dream2.0）](https://github.com/nineya/halo-theme-dream2.0)
进行二次重构移植的 WordPress 主题。

- 原始Halo主题：[nineya/halo-theme-dream2.0](https://github.com/nineya/halo-theme-dream2.0)
- WordPress 二次重构：[mxisc/Dream2.0-MXIN](https://github.com/mxisc/Dream2.0-MXIN)
- 原Halo主题作者：Nineya
- WordPress 重构与维护：铭心

## 安装

1. 下载主题包。
2. 在 WordPress 后台进入「外观 → 主题 → 安装主题 → 上传主题」。
3. 上传并启用主题。
4. 在「梦屿」中配置站点信息、外观、布局、内容及高级设置。
5. 本主题的侧栏以小工具形式配置，可在「外观-小工具」中进行配置。

## 评论表情

主题不内置评论表情。后台上传的表情保存在 `wp-content/uploads/dream2-mxin/emoji/`，不会随主题更新被覆盖。

需要使用梦屿同款表情时，可从[百度网盘下载表情包](https://pan.baidu.com/s/1A_GK4ypA-_4TLO9wszirjw?pwd=8ni3)，然后将压缩包直接解压到 `wp-content/uploads/`；主题会自动读取其中的分组和表情清单。提取码：`8ni3`。

后台单次最多上传 50 个表情，单个文件不超过 2 MB；每个分组最多 200 个文件、总计 50 MB。

## AI 站内问答

在「梦屿 → AI 配置」中完成服务商、API Key、模型和关联账户配置，并开启「启用 AI 站内问答」后，前台搜索浮层会默认进入「AI 问答」。角色卡可选；配置后用于站内问答和文章总结，留空时不发送角色设定。问答只检索公开发布且未设置密码的文章和页面；有直接相关资料时列出来源，资料不足时简短说明。

开启「启用文章内 AI 总结」后，公开文章正文前会显示简短总结。增强插件可单独开启看板娘总结；两处总结共用结果。站内问答和文章总结分别提供内置功能提示词，默认只读；点击「编辑提示词」并确认风险后可修改。修改提示词可能影响角色口吻、准确性与安全约束；保存后对应缓存重新生成。

「梦屿 → AI 请求记录」可查看站内问答与文章总结的模型请求结果、HTTP 状态、耗时和错误类别。失败记录可展开查看传输错误、上游错误或生成中断详情；流式请求与普通请求通过请求 ID 对照。记录保留最近 30 天、最多 500 条；缓存命中不会发起模型请求。记录不包含完整问题、文章、提示词或密钥；旧记录无法补回当时的诊断详情。

## 兼容性

- WordPress 6.4+
- PHP 8.0+

## 授权

原 Dream 主题 Copyright (c) 2021 Nineya，采用 MIT License。
本项目在保留原主题来源声明的基础上进行 WordPress 二次重构，新增及移植代码同样采用 MIT License。
详见 `LICENSE`。
