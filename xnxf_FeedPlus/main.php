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
$ddf = array("m-now","");
$act = GetVars("act","GET");
if ($act=="lucky"){
  $ddf[0] = "";
  $ddf[1] = "m-now";
}

$strHash = "gfg";
$Post =  FeedPlus_GetLucky($strHash);
$LuckyUrl = $Post == null ? "" : $Post->Url;

?>
<style type="text/css">
td > input[type="text"]{
width:96%;
}
</style>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle;?></div>
  <div class="SubMenu"><a href="main.php"><span class="m-left <?php echo $ddf[0] ?>">首页</span></a><a href="<?php echo $zbp->host?>zb_system/admin/edit.php?act=ArticleEdt&type=Feed"><span class="m-left">新建RSS</span></a><a href="main.php?act=lucky"><span class="m-left <?php echo $ddf[1] ?>">手慢无</span></a></div>
  <div id="divMain2">
<?php if($act=="lucky" || $act=="save") { ?>
<?php
$LuckyData = json_decode(file_get_contents(FeedPlus_Path('LuckyDB')));
$LuckyCFG = &$LuckyData->Lucky;
$strCodes = join("\n",$LuckyData->Codes);
if($act=="save"){
  if ($LuckyCFG->Time < time())
    $LuckyCFG->Time = time() + 1800;
  $LuckyCFG->PageID = trim(GetVars("edtPageID","POST"));
  $LuckyCFG->Title = GetVars("edtTitle","POST");
  $LuckyCFG->Link = trim(GetVars("edtLink","POST"));
  $LuckyCFG->Salt = (int)trim(GetVars("edtSalt","POST"));
  $LuckyCFG->Content = GetVars("txaContent","POST");
  $strCodes = str_replace("\r\n","\n",GetVars("txaCodes","POST"));
  $LuckyData->Codes = explode("\n",$strCodes);
  file_put_contents(FeedPlus_Path("LuckyDB"),json_encode($LuckyData));
  $zbp->SetHint('good');
  Redirect("main.php?act=lucky");
}
?>
    <form action="main.php?act=save" method="post">
    <table width="100%">
      <tr>
        <th width="7%">项目</th>
        <th width="30%">内容</th>
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
            <label onclick="$('#edtLink').val(this.innerText);">
            <input type="radio" name="radioLink" />          {%host%}?id={%PageID%}&hash={%hash%}&rewrite=1
            </label>
          </p>
          <p>
            <label onclick="$('#edtLink').val(this.innerText);">
            <input type="radio" name="radioLink" />
            {%host%}lucky/{%hash%}.html
            </label>
          </p>
          <p>{%hash%}值自动生成且每天一换；验证无效时会301到绑定的页面网址；</p>
        </td>
      </tr>
      <tr>
        <td><label for="edtSalt">链接混淆：</label></td>
        <td><input type="text" name="edtSalt" value="<?php echo $LuckyCFG->Salt ;?>" /></td>
        <td>任意正整数，为{%hash%}值的生成加盐</td>
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