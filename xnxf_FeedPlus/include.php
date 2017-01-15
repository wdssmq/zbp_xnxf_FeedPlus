<?php
//注册插件
RegisterPlugin("xnxf_FeedPlus", "ActivePlugin_xnxf_FeedPlus");
function ActivePlugin_xnxf_FeedPlus()
{
  Add_Filter_Plugin('Filter_Plugin_Admin_TopMenu', 'FeedPlus_AddMenu');
  Add_Filter_Plugin('Filter_Plugin_PostArticle_Core', 'FeedPlus_Post');
  Add_Filter_Plugin('Filter_Plugin_Edit_Begin', 'FeedPlus_Get');
  Add_Filter_Plugin('Filter_Plugin_ViewAuto_Begin', 'FeedPlus_Rewrite');
  Add_Filter_Plugin('Filter_Plugin_ViewPost_Template', 'FeedPlus_LuckyView');
  Add_Filter_Plugin('Filter_Plugin_BetterFeed_AddItem', 'FeedPlus_AddToFeed');
  // Add_Filter_Plugin('Filter_Plugin_BetterFeed_AddItem', 'FeedPlus_GetLucky');
}
function FeedPlus_AddToFeed(&$Posts)
{
  $DataDB        = json_decode(file_get_contents(FeedPlus_Path("PostDB")));
  $DataDB->Posts = (array) $DataDB->Posts;
  foreach ($DataDB->Posts as $k => $v) {
    $Post = json_decode($v);
    if ($Post->Public == 0) {
      $Posts[]=$Post;
    }
  }
  $Posts[]=FeedPlus_GetLucky();
  return null;
}
function FeedPlus_AddMenu(&$m)
{
  global $zbp;
  $m[] = MakeTopMenu("root", 'FeedPlus', $zbp->host . "zb_users/plugin/xnxf_FeedPlus/main.php", "", "FeedPlus");
}
function FeedPlus_Edit()
{
  echo "<script src='" . FeedPlus_Path("Script") . "' type='text/javascript'></script>";
}
function FeedPlus_Edit_Link()
{
  global $xnxf_link;
  echo '<script type="text/javascript">var sEdtLink = "' . $xnxf_link . '"</script>';
}
$xnxf_link = "";
function FeedPlus_Rewrite($url)
{
  global $zbp;
  $url       = trim(urldecode($url), '/');
  $LuckyData = json_decode(file_get_contents(FeedPlus_Path('LuckyDB')));
  $LuckyCFG =& $LuckyData->Lucky;
  $r = UrlRule::OutputUrlRegEx(str_replace("{%hash%}", "{%alias%}", $LuckyCFG->Link), 'page');
  $m = array();
  if (preg_match($r, $url, $m) == 1) {
    $_GET["hash"] = $m[1];
    $result       = ViewPost($LuckyCFG->PageID, $LuckyCFG->PageID, false);
    if ($result == false) {
      $zbp->ShowError(2, __FILE__, __LINE__);
    } else {
      $GLOBALS['hooks']['Filter_Plugin_ViewAuto_Begin']['FeedPlus_Rewrite'] = PLUGIN_EXITSIGNAL_RETURN;
      return null;
    }
  }
}
function FeedPlus_LuckyView(&$template)
{
  global $zbp;
  $hash = GetVars("hash", "GET");
  if ($hash != null) {
    $LuckyData = json_decode(file_get_contents(FeedPlus_Path('LuckyDB')));
    $title =& $template->GetTags('title');
    $article =& $template->GetTags('article');
    $Post = FeedPlus_GetLucky($hash);
    if ($Post != null && $hash == GetVars("hash", "GET")) {
      $title                = $Post->Title;
      $article->Title       = $Post->Title;
      $article->Content     = $Post->Content;
      // $article->IsLock = True;
      $GLOBALS['LuckyLink'] = $Post->Url;
      Add_Filter_Plugin('Filter_Plugin_Post_Url', 'FeedPlus_LuckyLink');
    } else {
      Redirect301($article->Url);
    }
  }
}
function FeedPlus_LuckyLink($post)
{
  $GLOBALS['hooks']['Filter_Plugin_Post_Url']['FeedPlus_LuckyLink'] = PLUGIN_EXITSIGNAL_RETURN;
  return $GLOBALS['LuckyLink'];
}
function FeedPlus_GetLucky(&$hash = "")
{
  global $zbp;
  $LuckyData = json_decode(file_get_contents(FeedPlus_Path('LuckyDB')));
  $LuckyCFG =& $LuckyData->Lucky;
  $NumsDays = floor((time() + $LuckyCFG->Salt) / 86400);
  $strHash  = substr(base_convert($NumsDays * $LuckyCFG->Salt, 10, 36), -6);
  $index    = $NumsDays % count($LuckyData->Codes);
  $code     = $LuckyData->Codes[$index];
  if (strpos($code, "*") !== false) {
    $arrCode = explode("*", $code);
    if ($arrCode[1] !== (string) $NumsDays && $hash != $strHash)
      return null;
    $code = $arrCode[0];
  }
  if ($code == "")
    return null;
  if ($hash != $strHash) {
    if ($LuckyCFG->Time > time())
    //return null;
      file_put_contents(FeedPlus_Path("Log", "p"), GetGuestAgent() . "\n", FILE_APPEND);
    $hash = $strHash;
    $code = "--<a href=\"{%url%}\" title=\"点击查看详情\">点击查看详情</a>--";
  } else {
    $LuckyData->Codes[$index] = $code . "*" . (string) $NumsDays;
    file_put_contents(FeedPlus_Path("LuckyDB"), json_encode($LuckyData));
  }
  $Post           = new FeedPlus_Post();
  // $Post->Time   = $LuckyCFG->Time;
  $Post->Title    = $LuckyCFG->Title;
  $Post->Url      = str_replace("{%host%}", $zbp->host, $LuckyCFG->Link);
  $Post->Url      = str_replace("{%hash%}", $strHash, $Post->Url);
  $Post->Url      = str_replace("{%PageID%}", $LuckyCFG->PageID, $Post->Url);
  $Post->Content  = str_replace("{%code%}", $code, $LuckyCFG->Content);
  $Post->Content  = str_replace("{%url%}", $Post->Url, $Post->Content);
  $Post->Content  = str_replace("{%feed%}", $zbp->host . "feed.php", $Post->Content);
  $Post->PostTime = $LuckyCFG->Time;
  return $Post;
}
function FeedPlus_Get()
{
  global $article;
  global $xnxf_link;
  if (isset($_GET['type']) && $_GET['type'] == "Feed") {
    if (isset($_GET['id'])) {
      $DataDB             = json_decode(file_get_contents(FeedPlus_Path("PostDB")));
      $DataDB->Posts      = (array) $DataDB->Posts;
      $Post              = json_decode($DataDB->Posts["ID" . $_GET['id']]);
      $article->AuthorID = $Post->User;
      $article->Status   = $Post->Public;
      $article->Title    = $Post->Title;
      $article->PostTime = $Post->PostTime;
      $article->Content  = $Post->Content;
      $xnxf_link         = $Post->Url;
      Add_Filter_Plugin('Filter_Plugin_Edit_Response5', 'FeedPlus_Edit_Link');
    }
    Add_Filter_Plugin('Filter_Plugin_Edit_Response5', 'FeedPlus_Edit');
  }
}
function FeedPlus_Post(&$article)
{
  global $zbp;
  if ($article->CateID == "Feed") {
    if (!is_file(FeedPlus_Path("PostDB"))) {
      $DataDB = new FeedPlus_Posts();
    } else {
      $DataDB        = json_decode(file_get_contents(FeedPlus_Path("PostDB")));
      $DataDB->Posts = (array) $DataDB->Posts;
    }
    if ($article->ID == 0) {
      $DataDB->MaxID += 1;
      $article->ID = $DataDB->MaxID;
    }
    $Post                           = new FeedPlus_Post();
    $Post->ID                       = $article->ID;
    $Post->Public                   = (int) $article->Status;
    $Post->User                     = (int) $article->AuthorID;
    $Post->Title                    = $article->Title;
    $Post->PostTime                 = $_POST['PostTime'];
    $Post->Url                      = $_POST['edtLink'];
    $Post->Content                  = $article->Content;
    $DataDB->Posts["ID" . $Post->ID] = json_encode($Post);
    file_put_contents(FeedPlus_Path("PostDB"), json_encode($DataDB));
    $zbp->BuildModule();
    $zbp->SaveCache();
    $zbp->SetHint('good');
    Redirect(FeedPlus_Path("Main"));
  }
}
function FeedPlus_Path($path)
{
  global $zbp;
  $MyPath = $zbp->path . "zb_users/plugin/xnxf_FeedPlus/";
  $MyHost = $zbp->host . "zb_users/plugin/xnxf_FeedPlus/";
  switch ($path) {
    case "Log":
      return $MyPath . "cache/Log.json";
      break;
    case "PostDB":
      return $MyPath . "db/Posts.json";
      break;
    case "LuckyDB":
      return $MyPath . "db/#%20" . $zbp->guid . ".json";
      break;
    case "IndexDB":
      return $MyPath . "db/index.html";
      break;
    case "Script":
      return $MyHost . "source/script.js";
      break;
    case "Main":
      return $MyHost . "main.php";
      break;
    default:
      // $fname = $this->dir . "post/" . $name . ".html";
  }
}
function InstallPlugin_xnxf_FeedPlus()
{
  if (!is_file(FeedPlus_Path('PostDB'))) {
    @mkdir(dirname(FeedPlus_Path('PostDB')), 0755);
  }
  if (!is_file(FeedPlus_Path('Log'))) {
    @mkdir(dirname(FeedPlus_Path('Log')), 0755);
  }
  if (!is_file(FeedPlus_Path('LuckyDB'))) {
    $data = '{"Lucky":{"Time":1,"PageID":1,"Title":"【手慢无】点击查看福利","Link":"{%host%}?id={%PageID%}&hash={%hash%}&rewrite=1","Salt":123456,"Content":"不定期通过Feed发放福利<br />{%code%}<br />强烈建议使用下边地址订阅收取福利通知：<br /><a href=\"{%feed%}\" target=\"_blank\" title=\"{%feed%}\">{%feed%}</a>"},"Codes":[""]}';
    file_put_contents(FeedPlus_Path('LuckyDB'), $data);
  }
  if (!is_file(FeedPlus_Path('IndexDB'))) {
    file_put_contents(FeedPlus_Path('IndexDB'), "");
  }
}
function UninstallPlugin_xnxf_FeedPlus()
{
}
class FeedPlus_Posts
{
  public $MaxID = 0;
  public $Posts = array();
}
class FeedPlus_Post
{
  public $ID = 0;
  public $Public = 0;
  public $User = 1;
  public $Title = "未命名文章";
  public $Url = "";
  public $Content = "";
  public $PostTime;
}
?>
