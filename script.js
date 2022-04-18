/**
 * Copyright 2022 Mr.true.China

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 *     http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// 设置快捷键
// jQuery(document).ready(function)：当 document 加载完毕后执行函数
// document：js 的“文档节点”对象，表示整个 HTML
jQuery(document).ready(() => {
    // 如果现在的动作是显示页面则进行处理，否则不处理
    // JSINFO：DokuWiki 提供的将 PHP 的 $JSINFO 提供给 JS 使用的对象
    if (JSINFO && JSINFO.act === 'show') {

        // 绑定按键事件
        jQuery(document).keypress((e) => {

            // window.console.log(e);

            // 获取设置中的快捷键按钮，在 action.php 里已经转为 key 数组
            let configKey = JSINFO.tagsearchbynamespace.config.shortcutKey;
            let needCtrlKey = JSINFO.tagsearchbynamespace.config.needCtrlKey;
            let needAltKey = JSINFO.tagsearchbynamespace.config.needAltKey;

            // window.console.log('configKey = ', configKey);

            // 是否按下了快捷键，快捷键可以设置多个，只要按下的按键在其中就算作按下
            let key = (configKey.indexOf(e.code) > -1);
            // 是否按下了 Ctrl 键，如果设置里不需要 Ctrl 键视为按下
            let ctrlKey = needCtrlKey ? e.ctrlKey : true;
            // 是否按下了 Alt 键，如果设置里不需要 Alt 键视为按下
            let altKey = needAltKey ? e.altKey : true;

            // 如果按下了 Ctrl+Alt+快捷键
            if (key && ctrlKey && altKey) {
                // 打开搜索页面，就是在当前网址后面拼上 plugin_tagsearchbynamespace__showsearchpage
                location.replace(window.location.href + '&do=plugin_tagsearchbynamespace__showsearchpage');
            }
        });
    }
});
