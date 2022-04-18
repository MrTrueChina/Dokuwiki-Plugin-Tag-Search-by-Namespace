<?php

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

namespace dokuwiki\plugin\tagsearchbynamespace;

use dokuwiki\Menu\Item\AbstractItem;

/**
 * 在右侧工具栏里添加的打开搜索页面的按钮
 */
class SearchButton extends AbstractItem
{
    // 点击这个按钮的时跳转页面携带的 do 属性
    protected $type = 'plugin_tagsearchbynamespace__showsearchpage';
    // 图标路径，使用的是 svg 图标
    protected $svg = __DIR__ . '/images/searchButton.svg';
    // 点击按钮时使用 POST 方式发出请求
    protected $method = 'post';

    // 覆写的获取按钮标题方法
    public function getLabel()
    {
        // 获取 action 组件获取国际化文本，这个类不是主要的组件类没有获取国际化文本的功能
        return plugin_load('action', 'tagsearchbynamespace')->getLang('searchPageButtonTitle');
    }
}
