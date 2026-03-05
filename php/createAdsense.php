<?php
require_once '../env.php'; //環境変数読み込み
require_once './settings.php'; //ルートディレクトリ読み込み
require_once DIR_ROOT . '/php/myAutoLoad.php'; //自動読み込み
require_once DIR_ROOT . '/php/functions/authAPIforUse.php'; //APIが有効かどうか自動判定
require_once DIR_ROOT . '/php/functions/authAccountforUse.php'; //ログイン状態が有効かどうか判定

// $id, $token, $secretId は authAccountforUse.php で設定済み

$adsenseTitle = "";
if (isset($_POST['title'])) {
  $adsenseTitle = str_replace("'", "\\'", $_POST['title']);
}

$adsenseDescription = "";
if (isset($_POST['description'])) {
  $adsenseDescription = str_replace("'", "\\'", $_POST['description']);
}

$jumpUrl = "";
if (isset($_POST['jumpUrl'])) {
  $jumpUrl = str_replace("'", "\\'", $_POST['jumpUrl']);
}

$startDate = date('Y-m-d');
if (isset($_POST['startDate'])) {
  $startDate = date('Y-m-d', strtotime($_POST['startDate']));
}

$endDate = time();
if (isset($_POST['endDate'])) {
  $endDate = strtotime($_POST['endDate']);
}

// contentsのbase64画像をURLに変換する
$contentsImgUrl = [];
if (isset($_POST['contents'])) {
  $contentsArray = json_decode($_POST['contents'], true);
  if (json_last_error() !== JSON_ERROR_NONE || !is_array($contentsArray)) {
    echo json_encode([
      'status' => 'invalid',
      'reason' => 'invalid contents format',
      'errCode' => 4000
    ]);
    exit;
  }
  foreach ($contentsArray as $base64) {
    $url = save_base64_image_to_server($base64);
    if ($url) {
      $contentsImgUrl[] = $url;
    } else {
      error_log("createAdsense.php: 画像の保存に失敗しました");
    }
  }
}

$contentsJson = str_replace("'", "\\'", json_encode($contentsImgUrl));

// 未使用なadsenseIdを生成
$adsenseId = SQLmakeRandomId('ads_list', 'adsenseId');

// 広告主のユーザーIDを設定（$id は authAccountforUse.php で設定済み）
$authorUserRandId = $id;

SQL("
insert into ads_list (adsenseId, authorUserRandId, adsenseTitle, adsenseDescription, contentsJson, jumpUrl, startDate, endDate)
values ('{$adsenseId}', '{$authorUserRandId}', '{$adsenseTitle}', '{$adsenseDescription}', '{$contentsJson}', '{$jumpUrl}', '{$startDate}', {$endDate});
");

echo json_encode([
  'status' => 'ok',
  'adsenseId' => $adsenseId,
  'contentsImgUrl' => $contentsImgUrl,
]);
