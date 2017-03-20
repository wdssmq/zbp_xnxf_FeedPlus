<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action='root';
if (!$zbp->CheckRights($action)) {$zbp->ShowError(6);die();}
if (!$zbp->CheckPlugin('xnxf_FeedPlus')) {$zbp->ShowError(48);die();}

$blogtitle='xnxf_FeedPlus';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
InstallPlugin_xnxf_FeedPlus();
$ddf = array("","","");
$act = GetVars("act","GET");
if ($act=="lucky"){
  $ddf[1] = "m-now";
} elseif($act=="pushbullet"){
  $ddf[2] = "m-now";
}else{
  $ddf[0] = "m-now";
}

$strHash = "gfg";
$Post =  FeedPlus_GetLucky($strHash);
$LuckyUrl = $Post == null ? "" : $Post->Url;
InstallPlugin_xnxf_FeedPlus();
UA_JSON::Del();
?>
<style type="text/css">
td > input[type="text"]{
width:96%;
}
</style>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle;?></div>
  <div class="SubMenu"><a href="main.php"><span class="m-left <?php echo $ddf[0] ?>">首页</span></a><a href="<?php echo $zbp->host?>zb_system/admin/edit.php?act=ArticleEdt&type=Feed"><span class="m-left">新建RSS</span></a><a href="main.php?act=lucky"><span class="m-left <?php echo $ddf[1] ?>">手慢无</span></a><a href="main.php?act=pushbul"><span class="m-left <?php echo $ddf[2] ?>">PushBullet</span></a></div>
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
          <p>
            <label onclick="$('#edtLink').val(this.innerText.trim());">
            <input type="radio" name="radioLink" />          {%host%}?id={%PageID%}&hash={%hash%}&rewrite=1
            </label>
          </p>
          <p>
            <label onclick="$('#edtLink').val(this.innerText.trim());">
            <input type="radio" name="radioLink" />
            {%host%}lucky/{%hash%}.html
            </label>
          </p>
          <p>{%hash%}值自动生成且每天一换；验证无效时会301到绑定的页面网址；</p>
        </td>
      </tr>
      <tr>
        <td colspan="2"><p><label for="txaContent">正文（同时用于RSS显示，可使用html;）：</label></p><textarea name="txaContent" id="txaContent" cols="67" rows="13"><?php echo TransferHTML($LuckyCFG->Content,"[html-format]");?></textarea></td>
        <td><p><label for="txaContent">福利码（一行一条,*表示已经被查看过，不影响再次显示）：</label></p><textarea name="txaCodes" id="txaCodes" cols="67" rows="13"><?php echo $strCodes; ?></textarea></td>
      </tr>
      <tr>
        <td colspan="3"><input type="submit" value="提交" /></td>
      </tr>
      <tr>
        <td colspan="3"><p>说明：</p><p>1、正文可用标签：{%code%}、{%host%}、{%feed%}、{%url%}；<br />2、在“链接”指向页面中，会按天提取一条福利码替换掉正文中的{%code%}显示（RSS中会替换成提示信息）；<br />3、当有新文章更新时才会触发RSS的生成</p></td>
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
  }
  $zbp->Config('FeedPlus')->PushKey = $PushKey;
  $zbp->Config('FeedPlus')->PubWord = trim(GetVars("PubWord","POST"));
  if (GetVars("PriReset", "POST") === "true")
    $zbp->Config('FeedPlus')->PriFeed = $zbp->feedurl . "?PriKey=" . md5(date("Ymdh") . GetVars("PushKey", "POST"));
  $zbp->SaveConfig('FeedPlus');
  $zbp->SetHint('good');
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
        <td><input id="PushKey" name="PushKey" style="width:95%;"  type="text" value="<?php echo $zbp->Config('FeedPlus')->PushKey;?>" /></td>
        <td></td>
      </tr>
      <tr>
        <td>公开关键词</td>
        <td><input id="PubWord" name="PubWord" style="width:95%;"  type="text" value="<?php echo $zbp->Config('FeedPlus')->PubWord;?>" placeholder="#FeedPlus#" /></td>
        <td>含有该关键词的信息将输出到RSS中；</td>
      </tr>
      <tr>
        <td>自用RSS</td>
        <td><input id="PriFeed" name="PriFeed" style="width:95%;" onblur="this.value='<?php echo $zbp->Config('FeedPlus')->PriFeed;?>'" type="text" value="<?php echo $zbp->Config('FeedPlus')->PriFeed;?>" onmouseover="this.select();" onclick="that = this;setTimeout(function(){that.select()},150);"/><input id="PriReset" name="PriReset" type="hidden" value="false" /></td>
        <td>私用Read it later <a href="javascript:;" onclick="$('#PriReset').val('true');$('#FP-form').submit()">生成新地址</a></td>
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
      "<td align='center'><a href='" . $zbp->host . "zb_system/admin/edit.php?act=ArticleEdt&amp;type=Feed&amp;id=" . $Post->ID . "'><img src='" . $zbp->host . "zb_system/image/admin/page_edit.png' alt='" . $zbp->lang['msg']['edit'] . "' title='" . $zbp->lang['msg']['edit'] . "' width='16' /></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a onclick='return window.confirm(\"" . $zbp->lang['msg']['confirm_operating'] . "\");' href='" . FeedPlus_Path("Main") . "?act=FeedDel&amp;id=" . $Post->ID . "'><img src='" . $zbp->host . "zb_system/image/admin/delete.png' alt='" .$zbp->lang['msg']['edit'] . "' title='" .$zbp->lang['msg']['edit'] . "' width='16' /></a></td>" .
    "</tr>";
  }
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