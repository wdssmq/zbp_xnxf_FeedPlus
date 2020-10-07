<?php
require __DIR__  . '/class/UA_JSON.php';
//注册插件
RegisterPlugin("xnxf_FeedPlus", "ActivePlugin_xnxf_FeedPlus");
function ActivePlugin_xnxf_FeedPlus()
{
  Add_Filter_Plugin('Filter_Plugin_Admin_TopMenu', 'xnxf_FeedPlus_AddMenu');
  Add_Filter_Plugin('Filter_Plugin_PostArticle_Core', 'xnxf_FeedPlus_Post');
  Add_Filter_Plugin('Filter_Plugin_Edit_Begin', 'xnxf_FeedPlus_Get');
  Add_Filter_Plugin('Filter_Plugin_ViewAuto_Begin', 'xnxf_FeedPlus_Rewrite');
  Add_Filter_Plugin('Filter_Plugin_ViewPost_Template', 'xnxf_FeedPlus_LuckyView');
  Add_Filter_Plugin('Filter_Plugin_ViewFeed_End', 'xnxf_FeedPlus_AddToFeed');
  // Add_Filter_Plugin('Filter_Plugin_BetterFeed_AddItem', 'xnxf_FeedPlus_GetLucky');
}
function xnxf_FeedPlus_AddToFeed(&$rss2)
{
  global $zbp;
  if (isset($rss2->break) && $rss2->break)
    return;
  if (GetVars("redirect", "GET") !== null)
    Redirect301($zbp->feedurl);
  $DataDB = json_decode(file_get_contents(xnxf_FeedPlus_Path("PostDB")));
  $DataDB->Posts = (array)$DataDB->Posts;
  foreach ($DataDB->Posts as $k => $v) {
    $Post = json_decode($v);
    if ($Post->Public == 0) {
      $rss2->addItem($Post->Title, $Post->Url, $Post->Content, $Post->PostTime);
    }
  }
  $Post = xnxf_FeedPlus_GetLucky();
  // if (UA_JSON::Add() && $Post !== null) {
  if ($Post !== null) {
    $Post->Content = str_replace("[lock]", "", $Post->Content);
    $Post->Content = str_replace("[/lock]", "", $Post->Content);
    $rss2->addItem($Post->Title, $Post->Url, $Post->Content, $Post->PostTime);
  }
  $FP_CFG = $zbp->Config('xnxf_FeedPlus');
  $PushKey = $FP_CFG->PushKey;
  if ($PushKey == "")
    return null;
  $PubWord = $FP_CFG->PubWord;
  $PriKey = GetVars("PriKey", "GET");
  if (VerifyWebToken($PriKey, 'PriKey')) {
    $PriKey = "65537";
  } else if (!empty($PriKey)) {
    $Post = new xnxf_FeedPlus_Article();
    $Post->Title = "PriKey已失效" . date("Ymd");
    $Post->Url = xnxf_FeedPlus_Path("Main", "host") . '?act=pushbul&date=' . date("Ymd");
    $Post->Content .= '--<a href="' . $Post->Url . '">点击这里重新获取</a>--';
    $Post->PostTime = time();
    $rss2->addItem($Post->Title, $Post->Url, $Post->Content, $Post->PostTime);
  }
  $http_post = Network::Create();
  $http_post->open('GET', 'https://api.pushbullet.com/v2/pushes?active=true');
  $http_post->setRequestHeader('Authorization', 'Basic ' . base64_encode($PushKey . ':' . $PushKey));
  $http_post->send();
  $objData = json_decode($http_post->responseText);
  // var_dump($objData);
  // echo $http_post->responseText;
  // die();
  if (empty($objData)) {
    return;
  }
  foreach ($objData->pushes as $item) {
    $PubThis = false;
    $Post = new xnxf_FeedPlus_Article();
    $Post->Title = isset($item->title) ? $item->title : (isset($item->file_name) ? $item->file_name : $item->body);
    $Post->Url = isset($item->url) ? $item->url : (isset($item->file_url) ? $item->file_url : $zbp->feedurl . "?redirect=" . $item->created);
    $Post->Content = trim(isset($item->body) ? $item->body : $Post->Title);
    if ($Post->Content == $PubWord)
      $Post->Content = $Post->Title . $Post->Content;
    if (strpos($Post->Content, $PubWord) !== false) {
      $Post->Title = str_replace($PubWord, "", $Post->Title) . " - $PubWord";
      $Post->Content = str_replace($PubWord, "", $Post->Content) . " - $PubWord";
      $PubThis = true;
    }
    if ($Post->Title !== $Post->Content && strpos($Post->Content, SubStrUTF8($Post->Title, 27)) == false)
      $Post->Content = SubStrUTF8($Post->Title, 27) . "</p><p>" . $Post->Content;
    $Post->Title = SubStrUTF8($Post->Title, 77);
    $Post->Content = "<p>" . $Post->Content . "</p>";
    // $Post->Content .= isset($item->url) ? '<p>点击查看：<a href="' . $item->url . '">' . $item->url . '</a></p>' : "";
    $Post->Content .= isset($item->image_url) ? '<p><img src="' . $item->image_url . '" alt="' . $Post->Title . '" /></p>' : "";
    // $Post->Content .= isset($item->file_url) ? '<p>点击查看/下载：<a href="' . $item->file_url . '">' . $Post->Title . '</a></p>' : "";
    $Post->PostTime = $item->created;
    if ($PriKey === "65537" || $PubThis)
      $rss2->addItem($Post->Title, $Post->Url, $Post->Content, $Post->PostTime);
  }
}
function xnxf_FeedPlus_AddMenu(&$m)
{
  global $zbp;
  $m[] = MakeTopMenu("root", 'xnxf_FeedPlus', $zbp->host . "zb_users/plugin/xnxf_FeedPlus/main.php", "", "FeedPlus");
}
function xnxf_FeedPlus_Rewrite($inpurl)
{
  global $zbp;
  $url = GetValueInArray(explode('?', $inpurl), '0');
  if ($zbp->cookiespath === substr($url, 0, strlen($zbp->cookiespath))) {
    $url = substr($url, strlen($zbp->cookiespath));
  }
  $url = trim(urldecode($url), '/');
  $LuckyData = json_decode(file_get_contents(xnxf_FeedPlus_Path('LuckyDB')));
  $LuckyCFG = &$LuckyData->Lucky;
  $r = UrlRule::OutputUrlRegEx(str_replace("{%hash%}", "{%alias%}", $LuckyCFG->Link), 'page');
  $m = array();
  if (preg_match($r, $url, $m) == 1) {
    $_GET["hash"] = $m[1];
    $result = ViewPost($LuckyCFG->PageID, $LuckyCFG->PageID, false);
    if ($result == false) {
      $zbp->ShowError(2, __FILE__, __LINE__);
    } else {
      $GLOBALS['hooks']['Filter_Plugin_ViewAuto_Begin']['xnxf_FeedPlus_Rewrite'] = PLUGIN_EXITSIGNAL_RETURN;
      return null;
    }
  }
}
function xnxf_FeedPlus_LuckyView(&$template)
{
  global $zbp;
  $hash = GetVars("hash", "GET");
  if ($hash != null) {
    $LuckyData = json_decode(file_get_contents(xnxf_FeedPlus_Path('LuckyDB')));
    $title = &$template->GetTags('title');
    $article = &$template->GetTags('article');
    $Post = xnxf_FeedPlus_GetLucky($hash);
    if ($Post != null && $hash == GetVars("hash", "GET")) {
      $title = $Post->Title;
      $article->Title = $Post->Title;
      $article->Content = $Post->Content;
      $article->IsLock = true;
      $GLOBALS['LuckyLink'] = $Post->Url;
      Add_Filter_Plugin('Filter_Plugin_Post_Url', 'xnxf_FeedPlus_LuckyLink');
    } else {
      Redirect301($article->Url);
    }
  }
}
function xnxf_FeedPlus_LuckyLink($post)
{
  $GLOBALS['hooks']['Filter_Plugin_Post_Url']['xnxf_FeedPlus_LuckyLink'] = PLUGIN_EXITSIGNAL_RETURN;
  return $GLOBALS['LuckyLink'];
}
function xnxf_FeedPlus_GetLucky(&$hash = "")
{
  global $zbp;
  $LuckyData = json_decode(file_get_contents(xnxf_FeedPlus_Path('LuckyDB')));
  $arrCodes = $zbp->Config('xnxf_FeedPlus')->LuckyCode;
  if (count($arrCodes) === 0)
    return;
  $LuckyCFG = &$LuckyData->Lucky;
  if ($LuckyCFG->Time > time() && $hash == "")
    return null;
  $NumsDays = floor((time() + $LuckyCFG->Time) / 86400);
  $strHash = substr(base_convert($NumsDays * $LuckyCFG->Time, 10, 36), -6);
  $index = $NumsDays % count($arrCodes);
  $code = trim($arrCodes[$index]);
  if (strpos($code, "*") !== false) {
    $arrCode = explode("*", $code);
    if ($arrCode[1] !== (string)$NumsDays && $hash !== $strHash)
      return null;
    $code = $arrCode[0];
  }
  if ($code === "" || $code == "--")
    return null;
  if ($hash !== $strHash) {
    $hash = $strHash;
    $code = "--<a href=\"{%url%}\" title=\"点击查看详情\">点击查看详情</a>--";
  } else {
    $arrCodes[$index] = $code . "*" . (string)$NumsDays;
    $zbp->Config('xnxf_FeedPlus')->LuckyCode = $arrCodes;
    $zbp->SaveConfig('xnxf_FeedPlus');
    file_put_contents(xnxf_FeedPlus_Path("LuckyDB"), json_encode($LuckyData));
  }
  $Post = new xnxf_FeedPlus_Article();
  $Post->Title = $LuckyCFG->Title;
  if ($zbp->option['ZC_STATIC_MODE'] !== 'REWRITE')
    $LuckyCFG->Link = "{%host%}?id={%PageID%}&hash={%hash%}&rewrite=1";
  $Post->Url = str_replace("{%host%}", $zbp->host, $LuckyCFG->Link);
  $Post->Url = str_replace("{%hash%}", $strHash, $Post->Url);
  $Post->Url = str_replace("{%PageID%}", $LuckyCFG->PageID, $Post->Url);
  $Post->Content = str_replace("{%code%}", $code, $LuckyCFG->Content);
  $Post->Content = str_replace("{%url%}", $Post->Url, $Post->Content);
  $Post->Content = str_replace("{%feed%}", $zbp->feedurl, $Post->Content);
  $Post->PostTime = $LuckyCFG->Time;
  return $Post;
}
function xnxf_FeedPlus_Edit()
{
  echo "<script src='" . xnxf_FeedPlus_Path("Script", "host") . "' type='text/javascript'></script>";
}
function xnxf_FeedPlus_Edit_Link()
{
  global $xnxf_link;
  echo '<script type="text/javascript">var sEdtLink = "' . $xnxf_link . '"</script>';
}
$xnxf_link = "";
function xnxf_FeedPlus_Get()
{
  global $article;
  global $xnxf_link;
  if (GetVars("type", "GET") == "Feed") {
    if (isset($_GET['id'])) {
      $DataDB = json_decode(file_get_contents(xnxf_FeedPlus_Path("PostDB")));
      $DataDB->Posts = (array)$DataDB->Posts;
      $Post = json_decode($DataDB->Posts["ID" . $_GET['id']]);
      $article->AuthorID = $Post->User;
      $article->Status = $Post->Public;
      $article->Title = $Post->Title;
      $article->PostTime = $Post->PostTime;
      $article->Content = $Post->Content;
      $xnxf_link = $Post->Url;
      Add_Filter_Plugin('Filter_Plugin_Edit_Response5', 'xnxf_FeedPlus_Edit_Link');
    }
    Add_Filter_Plugin('Filter_Plugin_Edit_Response5', 'xnxf_FeedPlus_Edit');
  }
}
function xnxf_FeedPlus_Post($article)
{
  global $zbp;
  if ($article->CateID === "Feed") {
    if (!is_file(xnxf_FeedPlus_Path("PostDB"))) {
      $DataDB = new xnxf_FeedPlus_Posts();
    } else {
      $DataDB = json_decode(file_get_contents(xnxf_FeedPlus_Path("PostDB")));
      $DataDB->Posts = (array)$DataDB->Posts;
    }
    if ($article->ID == 0) {
      $DataDB->MaxID += 1;
      $article->ID = $DataDB->MaxID;
    }
    $Post = new xnxf_FeedPlus_Article();
    $Post->ID = $article->ID;
    $Post->Public = (int)$article->Status;
    $Post->User = (int)$article->AuthorID;
    $Post->Title = $article->Title;
    $Post->PostTime = $_POST['PostTime'];
    $Post->Url = $_POST['edtLink'];
    $Post->Content = $article->Content;
    $DataDB->Posts["ID" . $Post->ID] = json_encode($Post);
    file_put_contents(xnxf_FeedPlus_Path("PostDB"), json_encode($DataDB));
    $zbp->BuildModule();
    $zbp->SaveCache();
    $zbp->SetHint('good');
    Redirect(xnxf_FeedPlus_Path("Main", "host"));
  }
}
function xnxf_FeedPlus_Path($file, $t = "path")
{
  global $zbp;
  $result = $zbp->$t . "zb_users/plugin/xnxf_FeedPlus/";
  switch ($file) {
    case "Log":
      return $result . "cache/Log.json";
      break;
    case "PostDB":
      return $result . "db/Posts.json";
      break;
    case "LuckyDB":
      return $result . "db/#%20" . $zbp->guid . ".json.lock";
      break;
    case "IndexDB":
      return $result . "db/index.html";
      break;
    case "Script":
      return $result . "source/script.js";
      break;
    case "Main":
      return $result . "main.php";
      break;
    default:
      return $result;
  }
}
function InstallPlugin_xnxf_FeedPlus()
{
  global $zbp;
  if (!$zbp->HasConfig('xnxf_FeedPlus')) {
    $zbp->Config('xnxf_FeedPlus')->PushKey = "";
    $zbp->Config('xnxf_FeedPlus')->PubWord = "#PubWord";
    // $zbp->Config('xnxf_FeedPlus')->PriFeed = md5(time());
    $zbp->Config('xnxf_FeedPlus')->LuckyCode = array();
    $zbp->SaveConfig('xnxf_FeedPlus');
  }
  $filePostDB = xnxf_FeedPlus_Path('PostDB');
  if (!is_file($filePostDB)) {
    @mkdir(dirname($filePostDB), 0755);
    $DataDB = new xnxf_FeedPlus_Posts();
    file_put_contents($filePostDB, json_encode($DataDB));
  }
  UA_JSON::Create();
  UA_JSON::Add();
  UA_JSON::Del();
  if (!is_file(xnxf_FeedPlus_Path('LuckyDB'))) {
    $data = '{"Lucky":{"Time":1,"PageID":1,"Title":"【手慢无】点击查看福利","Link":"{%host%}lucky/{%hash%}.html","Content":"<p>不定期通过Feed发放福利</p><p>{%code%}</p><p>强烈建议使用下边地址订阅收取福利通知：</p><p><a href=\"{%feed%}\" target=\"_blank\" title=\"{%feed%}\">{%feed%}</a></p>"}}';
    file_put_contents(xnxf_FeedPlus_Path('LuckyDB'), $data);
  }
  if (!is_file(xnxf_FeedPlus_Path('IndexDB'))) {
    file_put_contents(xnxf_FeedPlus_Path('IndexDB'), "");
  }
}
function UninstallPlugin_xnxf_FeedPlus()
{
}
function xnxf_FeedPlus_a($href, $title, $text = "")
{
  if (empty($text)) {
    $text = $href;
  }
  return "<a href=\"{$href}\" target=\"_blank\" title=\"{$title}\">$text</a>";
}
class xnxf_FeedPlus_Posts
{
  public $MaxID = 0;
  public $Posts = array();
}
class xnxf_FeedPlus_Article
{
  public $ID = 0;
  public $Public = 1;
  public $User = 1;
  public $Title = "未命名文章";
  public $Url = "";
  public $Content = "";
  public $PostTime;
}
