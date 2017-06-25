<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action='root';
if (!$zbp->CheckRights($action)) {$zbp->ShowError(6);die();}
if (!$zbp->CheckPlugin('xnxf_FeedPlus')) {$zbp->ShowError(48);die();}

$blogtitle='FeedPlus';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
InstallPlugin_xnxf_FeedPlus();
$ddf = array("","","");
$act = GetVars("act","GET");
if ($act=="lucky"){
  $ddf[1] = "m-now";
} elseif($act=="pushbul"){
  $ddf[2] = "m-now";
}else{
  $ddf[0] = "m-now";
}

$strHash = "65537";
$Post =  FeedPlus_GetLucky($strHash);
$LuckyUrl = $Post == null ? "" : $Post->Url;
InstallPlugin_xnxf_FeedPlus();
UA_JSON::Del();
?>
<script type="text/javascript" src="http://test.wdssmq.com/zbp/zb_users/plugin/xnxf_FeedPlus/MARKDOWN_IF_NEEDED.user.js"></script>
<style type="text/css">
td > input[type="text"]{
width:96%;
}
</style>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle;?></div>
  <div class="SubMenu"><a href="main.php"><span class="m-left <?php echo $ddf[0] ?>">首页</span></a><a href="<?php echo $zbp->host?>zb_system/admin/edit.php?act=ArticleEdt&type=Feed"><span class="m-left">新建RSS</span></a><a href="main.php?act=pushbul"><span class="m-left <?php echo $ddf[2] ?>">PushBullet</span></a><a href="main.php?act=lucky"><span class="m-left <?php echo $ddf[1] ?>">手慢无</span></a><?php require "about.php"?></div>
  <div id="divMain2">
<?php if($act=="lucky" || $act=="lucky-save") { ?>
<?php
$LuckyData = json_decode(file_get_contents(FeedPlus_Path('LuckyDB')));
$LuckyCFG =& $LuckyData->Lucky;
$strCodes = join("\n", $LuckyData->Codes);
if ($act == "lucky-save") {
  if ($LuckyCFG->Time < time()) {
    $LuckyCFG->Time = time() + 1877;
  }
  $LuckyCFG->PageID  = trim(GetVars("edtPageID", "POST"));
  $LuckyCFG->Title   = trim(GetVars("edtTitle", "POST"));
  if ($zbp->option['ZC_STATIC_MODE'] == 'REWRITE')
    $LuckyCFG->Link    = trim(GetVars("edtLink", "POST"));
  $LuckyCFG->Content = trim(GetVars("txaContent", "POST"));
  $strCodes          = str_replace("\r\n", "\n", trim(GetVars("txaCodes", "POST")));
  $LuckyData->Codes  = explode("\n", $strCodes);
  file_put_contents(FeedPlus_Path("LuckyDB"), json_encode($LuckyData));
  $zbp->SetHint('good');
  Redirect("main.php?act=lucky");
}
?>
    <form action="main.php?act=lucky-save" method="post">
    <table width="100%">
      <tr>
        <th width="7%">项目</th>
        <th width="45%">内容</th>
        <th>备注</th>
      </tr>
      <tr>
        <td><label for="edtPageID">绑定页ID</label></td>
        <td><input type="text" name="edtPageID" value="<?php echo $LuckyCFG->PageID ?>" /></td>
        <td>任意能访问的文章或页面ID; <?php echo "<a target=\"_blank\" href=\"$LuckyUrl\" title=\"手慢无\">$LuckyUrl</a>";?></td>
      </tr>
      <tr>
        <td><label for="edtTitle">标题：</label></td>
        <td><input type="text" name="edtTitle" value="<?php echo $LuckyCFG->Title ?>" /></td>
        <td><a href="<?php echo $zbp->feedurl?>" target="_blank"><?php echo $zbp->feedurl?></a></td>
      </tr>
      <tr>
        <td><label for="edtLink">链接模式：</label></td>
        <td><input type="text" name="edtLink" id="edtLink" value="<?php echo $LuckyCFG->Link ;?>" /></td>
        <td>
          <p>{%hash%}值自动生成且每天一换；验证无效时会301到绑定的页面网址；</p>
        </td>
      </tr>
      <tr>
        <td colspan="2"><p><label for="txaContent">正文（同时用于RSS显示，可使用html;）：</label></p><p class="md-toolbar"><a href="https://greasyfork.org/zh-CN/scripts/29203-markdown-if-needed" title="markdown-if-needed">点击安装<code>MarkDown</code>脚本</a> 即可使用MD语法并在这里显示工具条。</p><textarea class="md-needed" name="txaContent" id="txaContent" rows="13" style="width: 100%;"><?php echo TransferHTML($LuckyCFG->Content,"[html-format]");?></textarea></td>
        <td><p><label for="txaContent">福利码（一行一条,*表示已经被查看过，不影响再次显示）：</label></p><textarea name="txaCodes" id="txaCodes" cols="67" rows="13"><?php echo $strCodes; ?></textarea></td>
      </tr>
      <tr>
        <td colspan="3"><input class="md-submit" type="submit" value="提交" /></td>
      </tr>
      <tr>
        <td colspan="3"><p>说明（仍然不懂的话请点右上关于联系我）：</p><p>1、正文可用标签：{%code%}、{%host%}、{%feed%}、{%url%}；</p><p>2、在“链接”指向页面中，会按天提取一条福利码替换掉正文中的{%code%}显示（RSS中会替换成提示信息）；</p><p>3、将某行用双短线 -- 占位则可跳过当天的显示，从而实现每隔N天提取一条显示；</p><p>4、提取的起点很可能不是第一条；如果使用间隔提取则结尾也应该添加对应行数的占位符；</p><br /></td>
      </tr>
    </table>
    </form>
<?php }elseif($act=="pushbul" || $act=="pushbul-save") { ?>
<?php
if ($act == "pushbul-save") {
  $PushKey = trim(GetVars("PushKey", "POST"));
  $http_post  = Network::Create();
  $http_post->open('GET', 'https://api.pushbullet.com/v2/users/me');
  $http_post->setRequestHeader('Authorization', 'Basic ' . base64_encode($PushKey . ':' . $PushKey));
  $http_post->send();
  $objData = json_decode($http_post->responseText, true);
  if (!isset($objData['name']) || isset($result['error'])) {
    $PushKey = "";
    $zbp->SetHint('bad', 'Access Token 无效或网络不可用');
  }else{
    $zbp->SetHint('good');
  }
  $zbp->Config('FeedPlus')->PushKey = $PushKey;
  $zbp->Config('FeedPlus')->PubWord = trim(GetVars("PubWord","POST"));
  if (GetVars("PriReset", "POST") === "true")
    $zbp->Config('FeedPlus')->PriFeed = md5(date("Ymdh") . GetVars("PushKey", "POST"));
  $zbp->SaveConfig('FeedPlus');
  Redirect("main.php?act=pushbul");
}
?>
    <form id="FP-form" action="main.php?act=pushbul-save" method="post">
    <table width="100%">
      <tr>
        <th width="10%">项目</th>
        <th width="45%">内容</th>
        <th>备注</th>
      </tr>
      <tr>
        <td>Access Token</td>
        <td><input id="PushKey" name="PushKey" style="width:95%;"  type="text" value="<?php echo $zbp->Config('FeedPlus')->PushKey;?>" onmouseover="this.select();"></td>
        <td></td>
      </tr>
      <tr>
        <td>公开关键词</td>
        <td><input id="PubWord" name="PubWord" style="width:95%;"  type="text" value="<?php echo $zbp->Config('FeedPlus')->PubWord;?>" placeholder="#PubWord#" onmouseover="this.select();" onblur="if(this.value=='') this.value='#PubWord#'" onfocus="if(this.value=='#PubWord#') this.value=''"/></td>
        <td>含有该关键词的信息将输出到RSS中；</td>
      </tr>
      <tr>
        <td>自用RSS</td>
        <td><input id="PriFeed" name="PriFeed" style="width:95%;" onblur="this.value='<?php echo $zbp->feedurl . "?PriKey=" . $zbp->Config('FeedPlus')->PriFeed;?>'" type="text" value="<?php echo $zbp->feedurl . "?PriKey=" . $zbp->Config('FeedPlus')->PriFeed;?>" onmouseover="this.select();" onclick="that = this;setTimeout(function(){that.select()},150);"/><input id="PriReset" name="PriReset" type="hidden" value="false" /></td>
        <td>私用Read it later <a href="javascript:;" onclick="$('#PriReset').val('true');$('#FP-form').submit()">生成新地址</a> （每小时只能修改一次）</td>
      </tr>
      <tr>
        <td colspan="3"><input type="submit" value="提交" /></td>
      </tr>
    </table>
    </form>
<?php }else{?>
    <table width="100%">
    <tbody>
      <tr>
        <th width="3%">ID</th>
        <th width="8%">用户名</th>
        <th>标题</th>
        <th>链接</th>
        <th width="14%">时间</th>
        <th width="5%">类型</th>
        <th width="8%"></th>
      </tr>
<?php
if (is_file(FeedPlus_Path("PostDB"))){
  $DataDB = json_decode(file_get_contents(FeedPlus_Path("PostDB")));
  $DataDB->Posts = (array)$DataDB->Posts;
  foreach($DataDB->Posts as $k=>$v){
    $Post = json_decode($v);
    $author = new Member;
    $author = $zbp->GetMemberByID($Post->User);
    echo "<tr>" .
      "<td>" . $Post->ID . "</td>" .
      "<td>" . $author->StaticName . "</td>" .
      "<td>" . $Post->Title . "</td>" .
      "<td><a href='" . $Post->Url . "' title='" . $Post->Title . "' target='_blank'>" . $Post->Url . "</a></td>" .
      "<td>" . Date("Y-m-d h:i:s", $Post->PostTime) . "</td>" .
      "<td>" . $zbp->lang['post_status_name'][$Post->Public] . "</td>" .
      "<td align='center'><a href='" . $zbp->host . "zb_system/admin/edit.php?act=ArticleEdt&amp;type=Feed&amp;id=" . $Post->ID . "'><img src='" . $zbp->host . "zb_system/image/admin/page_edit.png' alt='" . $zbp->lang['msg']['edit'] . "' title='" . $zbp->lang['msg']['edit'] . "' width='16' /></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a onclick='return window.confirm(\"" . $zbp->lang['msg']['confirm_operating'] . "\");' href='" . FeedPlus_Path("Main","host") . "?act=FeedDel&amp;ID=" . $Post->ID . "'><img src='" . $zbp->host . "zb_system/image/admin/delete.png' alt='" .$zbp->lang['msg']['edit'] . "' title='" .$zbp->lang['msg']['edit'] . "' width='16' /></a></td>" .
    "</tr>";
  }
}
if ($act == "FeedDel") {
  $FeedID = trim(GetVars("ID", "GET"));
  unset($DataDB->Posts["ID$FeedID"]);
  var_dump($DataDB->Posts);
  file_put_contents(FeedPlus_Path("PostDB"),json_encode($DataDB));
  $zbp->SetHint('good');
  Redirect("main.php");
}
?>
    </tbody>
    </table>
<?php } ?>
  </div>
</div>
<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>