<?php
//注册插件
RegisterPlugin("xnxf_FeedPlus", "ActivePlugin_xnxf_FeedPlus");
function ActivePlugin_xnxf_FeedPlus() {
  Add_Filter_Plugin('Filter_Plugin_PostArticle_Core', 'FeedPlus_Post');
  Add_Filter_Plugin('Filter_Plugin_Edit_Begin', 'FeedPlus_Get');
}
function InstallPlugin_xnxf_FeedPlus() {
}
function UninstallPlugin_xnxf_FeedPlus() {
}
function FeedPlus_Edit() {
  echo "<script src='" . FeedPlus_Path("Script") . "' type='text/javascript'></script>";
}
function FeedPlus_Edit_Link() {
  global $xnxf_link;
  echo '<script type="text/javascript">var sEdtLink = "' . $xnxf_link . '"</script>';
}
$xnxf_link = "";
function FeedPlus_Get() {
  global $article;
  global $xnxf_link;
  if(isset($_GET['type']) && $_GET['type'] == "Feed") {
    if(isset($_GET['id'])) {
      $Posts             = json_decode(file_get_contents(FeedPlus_Path("PostDB")));
      $Posts->Posts      = (array) $Posts->Posts;
      $Post              = json_decode($Posts->Posts["ID" . $_GET['id']]);
      $article->AuthorID = $Post->User;
      $article->Status   = $Post->Public;
      $article->Title    = $Post->Title;
      $article->PostTime = $Post->PubDate;
      $article->Content  = $Post->Content;
      $xnxf_link = $Post->Link;
      Add_Filter_Plugin('Filter_Plugin_Edit_Response5', 'FeedPlus_Edit_Link');
    }
    Add_Filter_Plugin('Filter_Plugin_Edit_Response5', 'FeedPlus_Edit');
  }
}
function FeedPlus_Post(&$article) {
  global $zbp;
  if($article->CateID == "Feed") {
    if(!is_file(FeedPlus_Path("PostDB"))) {
      $Posts = new FeedPlus_Posts();
    }
    else {
      $Posts = json_decode(file_get_contents(FeedPlus_Path("PostDB")));
      $Posts->Posts = (array) $Posts->Posts;
      if($article->ID == 0) {
        $Posts->MaxID += 1;
      }
    }
    $Post                           = new FeedPlus_Post();
    $Post->ID                       = $Posts->MaxID;
    $Post->Public                   = (int) $article->Status;
    $Post->User                     = (int) $article->AuthorID;
    $Post->Title                    = $article->Title;
    $Post->PubDate                  = $_POST['PostTime'];
    $Post->Link                     = $_POST['edtLink'];
    $Post->Content                  = $article->Content;
    $Posts->Posts["ID" . $Post->ID] = json_encode($Post);
    file_put_contents(FeedPlus_Path("PostDB"), json_encode($Posts));
		$zbp->BuildModule();
		$zbp->SaveCache();
		$zbp->SetHint('good');
    Redirect(FeedPlus_Path("Main"));
  }
}
function FeedPlus_Path($path) {
  global $zbp;
  $MyPath = $zbp->path . "zb_users\plugin\xnxf_FeedPlus\db\\";
  $MyHost = $zbp->host . "zb_users/plugin/xnxf_FeedPlus/";
  switch($path) {
    case "PostDB":
      return $MyPath . "Posts.json";
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
class FeedPlus_Posts {
  public $MaxID = 1;
  public $Posts = array();
}
class FeedPlus_Post {
  public $ID = 0;
  public $Public = 0;
  public $User = 1;
  public $PubDate;
  public $Title = "未命名文章";
  public $Link = "";
  public $Content = "";
}
?>
