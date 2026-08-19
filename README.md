# Dream 2.0 MXIN

## v0.7.47

Dream 2.0 MXIN 是基于原版
[Dream（nineya/halo-theme-dream2.0）](https://github.com/nineya/halo-theme-dream2.0)
进行二次重构和 WordPress 原生移植的主题，并非原 Dream 项目的官方 WordPress 版本。

本项目以原 Dream 的视觉与交互设计为基准，保留二次元视觉、双侧栏布局、文章卡片、横幅、深色模式、
移动端抽屉导航和文章页样式，同时将内容、菜单、小工具、评论及主题配置重构为 WordPress 原生实现。
WordPress 版本的源码、安装包与更新请访问
[mxisc/Dream2.0-MXIN](https://github.com/mxisc/Dream2.0-MXIN)。

## 项目来源

- 原始主题：[nineya/halo-theme-dream2.0](https://github.com/nineya/halo-theme-dream2.0)
- WordPress 二次重构：[mxisc/Dream2.0-MXIN](https://github.com/mxisc/Dream2.0-MXIN)
- 原主题作者：Nineya
- WordPress 重构与维护：MXIN

## 安装

1. 将本目录压缩为 `dream2-mxin.zip`。
2. 在 WordPress 后台进入「外观 → 主题 → 安装主题 → 上传主题」。
3. 上传并启用主题。
4. 在「梦屿」中配置站点信息、外观、布局、内容及高级设置。
5. 在「外观 → 菜单」中为“主导航”分配菜单。

启用主题后，WordPress 登录与找回密码页面会自动使用梦屿样式，并统一失败响应、限制高频尝试；无需额外安装登录美化插件。

## 评论表情

主题不内置评论表情。后台上传的表情保存在 `wp-content/uploads/dream2-mxin/emoji/`，不会随主题更新被覆盖。

需要使用梦屿同款表情时，可从[百度网盘下载表情包](https://pan.baidu.com/s/1A_GK4ypA-_4TLO9wszirjw?pwd=8ni3)，然后将压缩包直接解压到 `wp-content/uploads/`；主题会自动读取其中的分组和表情清单。提取码：`8ni3`。

后台单次最多上传 50 个表情，单个文件不超过 2 MB；每个分组最多 200 个文件、总计 50 MB。

## 内置页面

在「梦屿 → 内容页面」中可启用文章归档页并配置路径。

分类和标签使用 WordPress 原生归档。

## 兼容性

- WordPress 6.4+
- PHP 8.0+
- WordPress 原生评论
- Yoast SEO
- CDN Enabler

本主题不包含其他博客系统的数据结构、接口或迁移兼容层。相册、动态、友链等能力将在需要时以
WordPress 原生页面模板、自定义文章类型或插件接口实现。

## 授权

原 Dream 主题 Copyright (c) 2021 Nineya，采用 MIT License。
本项目在保留原主题来源声明的基础上进行 WordPress 二次重构，新增及移植代码同样采用 MIT License。
详见 `LICENSE`。
