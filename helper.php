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

use \dokuwiki\Extension\Plugin;

/**
 * helper 组件，相当于其他地方的 库、前置、核心
 */
class helper_plugin_tagsearchbynamespace extends Plugin
{
    /**
     * 通过标签和标签命名空间获取使用到这些标签的页面的可以供 PageList 显示的页面信息，可以使用页面命名空间限制显示的页面<br/>
     * 支持的语法：<br/>
     * Tag1 Tag2：搜索包含 Tag1 和 Tag2 中至少一个的页面<br/>
     * Tag1 +Tag2：搜索包含 Tag1 且包含 Tag2 的页面<br/>
     * Tag1 -Tag2：搜索包含 Tag1 但不包含 Tag2 的页面
     * 
     * @param string $tags 标签和标签命名空间的字符串，支持语法
     * @param string $namespace 页面命名空间，使用这个参数将会只返回这个命名空间下的页面信息
     * @return array 可供 PageList 插进显示的页面信息列表
     */
    public function getPagesInfosByTagsAndTagsNamespaces($tags, $namespace = '')
    {
        // 加载 tag 插件的 helper 组件，如果没加载到则发出需要 tag 插件的 log，返回空数组
        if (!$tagHelper = $this->loadHelper('tag')) {
            dbglog($this->getLang('needTagPlugin'));
            return array();
        }

        // 加载 tagFilter 插件的 helper 组件，如果没加载到则发出需要 tagFilter 插件的 log，返回空数组
        if (!$tagFilterHelper = $this->loadHelper('tagfilter')) {
            dbglog($this->getLang('needTagFilterPlugin'));
            return array();
        }

        // 加载 tagFilter 插件的 syntax 组件，如果没加载到则发出需要 tagFilter 插件的 log，返回空数组
        if (!$tagFilterSyntax = $this->loadHelper('tagfilter_syntax')) {
            dbglog($this->getLang('needTagFilterPlugin'));
            return array();
        }

        // 把传入的标签字符串分割出单个的带运算符的标签
        $splitTags = array_map('trim', explode(' ', $tags));
        // dbglog('分割后的标签数组 = ');
        // dbglog($splitTags);

        // 把分割后的标签转为运算符和标签两部分
        $tagsWithOperator = array_map(fn ($t)=>$this->splitOpratorAndTag($t), $splitTags);
        // dbglog('$tagsWithOperator = ');
        // dbglog($tagsWithOperator);

        // 准备一个数组接收显示的页面的 ID
        $allPagesIds = array();

        // 遍历分割后的标签
        foreach($tagsWithOperator as $tagWithOperator){
            // 获取使用了这个标签的页面的 ID 列表
            $tagPagesIds = $this->getPagesIdsByTagOrNamespace($tagWithOperator['tag'], $namespace);
            // 按照运算符与现有的数组合并
            $allPagesIds = $this->mergePagesIdsByOprator($allPagesIds, $tagPagesIds, $tagWithOperator['operator']);
        }

        // 通过 TagFilter 的方法把页面 ID 转为页面信息
        $pagesInfos = $tagFilterSyntax->prepareList($allPagesIds, ['tagimagecolumn'=>array()]);

        // dbglog('转化后的页面信息列表 = ');
        // dbglog($pagesInfos);

        return $pagesInfos;
    }

    /**
     * 分割标签和运算符
     * 
     * @param string $tagWithOperator 带有运算符或不带运算符的单个标签
     * @return array 拆分后的运算符和标签数组，结构是：[[operator]=>运算符, [tag]=>标签]
     */
    public function splitOpratorAndTag($tagWithOperator)
    {
        // 没传入标签，返回两个空串
        if (!$tagWithOperator) {
            return ['operator' => '', 'tag' => ''];
        }

        // 去除前后的空字符
        $trimmedTag = trim($tagWithOperator);

        // 加号开头，是 + 运算符
        if (substr($trimmedTag, 0, 1) === '+') {
            return ['operator' => '+', 'tag' => substr($trimmedTag, 1)];
        }

        // 减号开头，是 - 运算符
        if (substr($trimmedTag, 0, 1) === '-') {
            return ['operator' => '-', 'tag' => substr($trimmedTag, 1)];
        }

        // 其他情况，没有运算符
        return ['operator' => '', 'tag' => $trimmedTag];
    }

    /**
     * 按照运算符合并页面 ID 数组
     * 
     * @param array $existIds 现有的 ID 列表
     * @param array $newIds 要合并进现有 ID 列表的 ID 列表
     * @param string $operator 运算符
     */
    public function mergePagesIdsByOprator($existIds, $newIds, $operator)
    {
        // 补充可能缺失的两个列表
        if(!$existIds){
            $existIds = array();
        }
        if(!$newIds){
            $newIds = array();
        }

        // 按照运算符进行合并
        switch ($operator){
            case '+':
                // 加号：保留现有列表中在新列表里也有的内容——取交集
                return array_intersect($existIds, $newIds);

            case '-':
                // 减号：从现有列表中移除在新列表里有的内容——取差集
                return array_diff($existIds, $newIds);

            default:
                // 其他情况，视为没有运算符：将两个列表合并——取并集
                return array_unique(array_merge($existIds, $newIds));
        }
    }

    /**
     * 通过标签和标签命名空间获取使用到这个标签的页面的 ID，已进行权限过滤
     * 
     * @param string $tags 标签或标签命名空间
     * @param string $namespace 页面命名空间，使用这个参数将会只返回这个命名空间下的页面信息
     * @return array 页面命名空间下使用了指定标签的页面的 ID
     */
    public function getPagesIdsByTagOrNamespace($tag, $namespace = '')
    {
        // 如果没有传入标签，直接返回空数组
        if(!$tag){
            return array();
        }

        // 加载 tagFilter 插件的 helper 组件，如果没加载到则发出需要 tagFilter 插件的 log，返回空数组
        if (!$tagFilterHelper = $this->loadHelper('tagfilter')) {
            dbglog($this->getLang('needTagFilterPlugin'));
            return array();
        }

        // 通过 TagFilter 的方法查询出标签到页面的映射
        $tagsToPages = $tagFilterHelper->getRelationsByTagRegExp($tag, $namespace);
        // 给标签拼接命名空间的通配后缀，再次查询，这次查询是查询命名空间下的所有标签
        $namespaceToPages = $tagFilterHelper->getRelationsByTagRegExp($tag.':.*', $namespace);

        // dbglog('调用 TagFilter 的方法获取在命名空间 ' . $namespace . ' 内使用了标签 ' . $tags . ' 的页面 = ');
        // dbglog($tagsToPages);

        // 把映射合并为一个页面 ID 列表
        $allPagesIds = array();
        foreach ($tagsToPages as $pages) {
            $allPagesIds = array_merge($allPagesIds, $pages);
        }
        foreach ($namespaceToPages as $pages) {
            $allPagesIds = array_merge($allPagesIds, $pages);
        }

        // 去重
        $allPagesIds = array_unique($allPagesIds);

        // 过滤掉没有浏览权限的页面
        $allPagesIds= array_filter($allPagesIds,'auth_quickaclcheck');

        // dbglog('合并后页面 ID 列表 = ');
        // dbglog($allPagesIds);

        return $allPagesIds;
    }
}
