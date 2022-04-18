# DokuWiki-Plugin-Tag-Search-by-Namespace

添加了一个使用标签和标签命名空间搜索页面的页面。

由于在注册时提示被识别为垃圾邮件我无法注册 DokuWiki，也就无法上传插件。

## 使用方式：

### 1. 进入搜索页面

如果使用的模板带有右侧工具栏则在右侧工具栏会显示一个打开搜索页面的按钮。

无论模板是否提供右侧工具栏都可以通过快捷键 Ctrl+i 打开搜索页面。

是否显示按钮和快捷键都可以在配置中修改。

### 2. 进行搜索

进入搜索页面后输入命名空间和标签后点击按钮即可进行搜索。

如果输入了命名空间则只显示在该命名空间下的页面，这里的命名空间指的是 DokuWiki 的页面命名空间。

输入标签可以使用标签或命名空间，输入命名空间则表示搜索该命名空间下的所有标签，这里的命名空间指的是 Tag 插件的标签命名空间

### 3. 使用运算符

本插件提供和 Tag 插件相同的运算符：

Tag1 Tag2：搜索两个标签中至少使用了一个的页面

Tag1 +Tag2：搜索在使用了 Tag1 的前提下使用了 Tag2 的页面

Tag1 -Tag2：搜索在使用了 Tag1 的前提下没有使用 Tag2 的页面
