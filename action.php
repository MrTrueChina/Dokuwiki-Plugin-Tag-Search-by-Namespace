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

use \dokuwiki\Extension\ActionPlugin;
use \dokuwiki\Extension\EventHandler;
use \dokuwiki\Extension\Event;
use \dokuwiki\plugin\tagsearchbynamespace\SearchButton;
use \dokuwiki\Form\Form;

// 当 form 发出 tagsearchbynamespace_searchbutton 的 POST 时这段代码会被调用，tagsearchbynamespace_searchbutton 是在后面写的独有字段
if (isset($_POST['tagsearchbynamespace_searchbutton'])) {
	// 声明全局变量
	global $tagsearchbynamespaceNamespaceText;
	global $tagsearchbynamespaceTagsText;
	// 把请求里的输入框文本保存进去
	$tagsearchbynamespaceNamespaceText = $_POST['namespaceInput'];
	$tagsearchbynamespaceTagsText = $_POST['tagsInput'];
}

/**
 * 这个插件的主类
 */
class action_plugin_tagsearchbynamespace extends ActionPlugin
{
	/**
	 * 注册，订阅各种事件。这是一个覆写的方法，参数现在的名字是 EventHandler
	 */
	public function register(EventHandler $controller)
	{
		// 订阅 DokuWiki 启动事件，在之后调用填充数据方法
		$controller->register_hook('DOKUWIKI_STARTED', 'AFTER',  $this, 'setDataToJsInfo');

		// 订阅在主窗口中输出 XHTML 事件，在之前调用显示查询页面方法
		$controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'showSearchPage', array());

		// 获取配置里的显示页面工具按钮设置，如果显示则添加按钮
		if ($this->getConf('showSearchButton')) {
			// 订阅菜单按钮装配事件，在之后调用添加按钮方法
			$controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'addsvgbutton');
		}
	}

	/**
	 * 向 $JSINFO 中填充需要的数据
	 */
	public function setDataToJsInfo(Event $event, $param)
	{
		// 从全局变量获取 JSINFO，这是一个存储多个信息的数组
		global $JSINFO;

		// 把设置里的是否需要按下 Crtl 和 Alt 设置放进 $JSINFO 里
		$JSINFO['tagsearchbynamespace']['config']['needCtrlKey'] = $this->getConf('needCtrlKey');
		$JSINFO['tagsearchbynamespace']['config']['needAltKey'] = $this->getConf('needAltKey');

		// 把设置里的打开编辑标签窗口的快捷键转为按键数组保存到 $JSINFO 里
		// explode：把字符串拆分为数组
		// array_map：遍历数组中的每个元素，执行方法进行处理，这里是 trim 方法，trim 是去除字符串两端的空字符
		$JSINFO['tagsearchbynamespace']['config']['shortcutKey'] = array_map('trim', explode(',', $this->getConf('shortcutKey')));
	}

	/**
	 * 添加右侧工具栏的 svg 按钮
	 */
	public function addsvgbutton(Event $event)
	{
		// 如果不在浏览页面则不处理
		if ($event->data['view'] != 'page') return;

		// 在按钮列表最后面追加一个按钮
		// 这里的逻辑是 $event->data['items'] 是右侧工具栏的按钮列表，创建一个按钮对象加到末尾就行
		$event->data['items'][] = new SearchButton();
	}

	/**
	 * 显示查询页面
	 */
	public function showSearchPage(Event $event, $param)
	{
		// 只对 plugin_tagsearchbynamespace__showsearchpage 的情况进行处理，这是打开搜索页面请求
		if ($event->data != 'plugin_tagsearchbynamespace__showsearchpage') {
			return;
		}

		// 把事件设为已使用
		$event->preventDefault();
		
        // 加载 tag 插件的 helper 组件，如果没加载到则发出需要 tag 插件的提示，不进行后续操作
        if (!$tagHelper = $this->loadHelper('tag')) {
            print '<h1>' . $this->getLang('needTagPlugin') . '</h1>';
            return;
        }

        // 加载 tagFilter 插件的 helper 组件，如果没加载到则发出需要 tagFilter 插件的提示，不进行后续操作
        if (!$tagFilterHelper = $this->loadHelper('tagfilter')) {
            print '<h1>' . $this->getLang('needTagFilterPlugin') . '</h1>';
            return;
        }

        // 加载 tagFilter 插件的 syntax 组件，如果没加载到则发出需要 tagFilter 插件的提示，不进行后续操作
        if (!$tagFilterSyntax = $this->loadHelper('tagfilter_syntax')) {
            print '<h1>' . $this->getLang('needTagFilterPlugin') . '</h1>';
            return;
        }

		// 绘制输入 UI
		$this->drawSearchForm();

		// 获取命名空间和标签的输入文本
		global $tagsearchbynamespaceNamespaceText;
		global $tagsearchbynamespaceTagsText;

		// 没有输入标签则不需要搜索，直接返回
		if (!$tagsearchbynamespaceTagsText) {
			return;
		}

		// 绘制搜索结果列表
		$this->drawFoundPageList($tagsearchbynamespaceTagsText, $tagsearchbynamespaceNamespaceText);
	}

	/**
	 * 绘制搜索页面的 form 表单 UI
	 */
	public function drawSearchForm()
	{
		// 绘制标题，使用国际化方法获取标题文本
		print '<h1>' . hsc($this->getLang('searchPagesByTags')) . '</h1>' . DOKU_LF;

		// 创建 form 表单并设为使用 POST
		$form = new Form(array('method' => 'post'));

		// 命名空间输入框的标题
		$nameSpaceLabel = $form->addLabel($this->getLang('searchPageNamespaceInputLabelText'));
		$nameSpaceLabel->attr('class', 'tagsearchbynamespace_form');

		// 命名空间输入框
		$namespaceInput = $form->addTextInput('namespaceInput');
		$namespaceInput->attr('class', 'tagsearchbynamespace_form');
		$namespaceInput->attr('id', 'tagsearchbynamespace_namespace_input');

		// 标签输入框的标题
		$tagLabel = $form->addLabel($this->getLang('searchPageTagsInputLabelText'));
		$tagLabel->attr('class', 'tagsearchbynamespace_form');

		// TODO：点击搜索后会刷新，输入框会缩回去，最好能改成不刷新的。
		// 标签输入框
		$tagsInputArea = $form->addTextarea('tagsInput');
		$tagsInputArea->attr('class', 'tagsearchbynamespace_form');
		$tagsInputArea->attr('id', 'tagsearchbynamespace_tags_input');

		// 搜索按钮
		$form->addButton('tagsearchbynamespace_searchbutton', $this->getLang('searchByttonText'));
		$form->attr('class', 'tagsearchbynamespace_form');

		// 绘制到页面上
		print $form->toHTML() . DOKU_LF;
	}

	/**
	 * 绘制找到的页面列表
	 * 
	 * @param $tags 查询的标签
	 * @param $namespace 页面命名空间，如果使用此参数则只显示在这个命名空间内的页面
	 */
	public function drawFoundPageList($tags, $namespace = '')
	{
		// 加载 helper 组件
		$helper = $this->loadHelper('tagsearchbynamespace');

		// 通过标签和命名空间查询页面数据
		$pagesInfos = $helper->getPagesInfosByTagsAndTagsNamespaces($tags, $namespace);

		if (!empty($pagesInfos)) {
			// 如果获取到了页面

			// 加载 PageList 插件的 helper 模块，获取不到则发出需要 Pagelist 插件的提示，不进行后续操作
			if (!$pagelistHelper = $this->loadHelper('pagelist')) {
				print '<h1>' . $this->getLang('needPagelistPlugin') . '</h1>';
				return false;
			}

			// 加载 Tag 插件的 helper 组件，如果加载不到的话在前面就结束执行了，这里不需要考虑加载不到的情况
			$tagHelper = $this->loadHelper('tag');

			// 从 Tag 插件获取 PageList 的 flags 配置，按照 Tag 插件的处理方式转化为数组
			$flags = explode(',', str_replace(" ", "", $tagHelper->getConf('pagelist_flags')));

			// 给 PageList 设置 flags
			$pagelistHelper->setFlags($flags);

			// 开启一个页面列表的绘制
			$pagelistHelper->startList();

			// 遍历页面，把页面添加进列表里
			foreach ($pagesInfos as $page) {
				$pagelistHelper->addPage($page);
			}

			// 结束 PageList 的列表，这应该会返回 HTML 对象，他应该也做了转义了
			print $pagelistHelper->finishList();
		} else {
			// 如果没有获取到页面

			// 加载 DokuWiki 的国际化对象
			global $lang;

			// 显示搜索不到内容文本
			print '<div"><p>' . $lang['nothingfound'] . '</p></div>';
		}
	}
}
