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

?>
<div id="divMain">
  <div class="divHeader"><?php echo $blogtitle;?></div>
  <div class="SubMenu"><a href="main.php"><span class="m-left">首页</span></a><a href="<?php echo $zbp->host?>zb_system/admin/edit.php?act=ArticleEdt&type=Feed"><span class="m-left">新建RSS</span></a></div>
  <div id="divMain2">
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
  $Posts = json_decode(file_get_contents(FeedPlus_Path("PostDB")));
  $Posts->Posts = (array)$Posts->Posts;
  foreach($Posts->Posts as $k=>$v){
    $Post = json_decode($v);
    $author = new Member;
    $author = $zbp->GetMemberByID($Post->User);
    echo "<tr>" .
      "<td>" . $Post->ID . "</td>" .
      "<td>" . $author->StaticName . "</td>" . 
      "<td>" . $Post->Title . "</td>" . 
      "<td><a href='" . $Post->Link . "' title='" . $Post->Title . "' target='_blank'>" . $Post->Link . "</a></td>" . 
      "<td>" . Date("Y-m-d h:i:s", $Post->PubDate) . "</td>" . 
      "<td>" . $zbp->lang['post_status_name'][$Post->Public] . "</td>" . 
      "<td align='center'><a href='" . $zbp->host . "zb_system/admin/edit.php?act=ArticleEdt&amp;type=Feed&amp;id=" . $Post->ID . "'><img src='" . $zbp->host . "zb_system/image/admin/page_edit.png' alt='" . $zbp->lang['msg']['edit'] . "' title='" . $zbp->lang['msg']['edit'] . "' width='16' /></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a onclick='return window.confirm(\"" . $zbp->lang['msg']['confirm_operating'] . "\");' href='" . FeedPlus_Path("Main") . "?act=FeedDel&amp;id=" . $Post->ID . "'><img src='" . $zbp->host . "zb_system/image/admin/delete.png' alt='" .$zbp->lang['msg']['edit'] . "' title='" .$zbp->lang['msg']['edit'] . "' width='16' /></a></td>" .
    "</tr>";
  }
}
?>
    </tbody>
    </table>
  </div>
</div>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>