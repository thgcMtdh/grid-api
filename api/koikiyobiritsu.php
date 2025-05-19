<?php

const DOWNLOAD_URL = "https://web-kohyo.occto.or.jp/kks-web-public/download/downloadCsv";

// デフォルトの形式をセットしておく
header('Content-Type: text/plain');

// CORS許可
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET");

// クエリパラメータを取得
$jhSybt     = isset($_GET['jhSybt'])     ? $_GET['jhSybt']     : '';
$tgtYmdFrom = isset($_GET['tgtYmdFrom']) ? $_GET['tgtYmdFrom'] : '';
$tgtYmdTo   = isset($_GET['tgtYmdTo'])   ? $_GET['tgtYmdTo']   : '';

// エスケープ
$jhSybt     = htmlspecialchars($jhSybt,     ENT_QUOTES, 'UTF-8');
$tgtYmdFrom = htmlspecialchars($tgtYmdFrom, ENT_QUOTES, 'UTF-8');
$tgtYmdTo   = htmlspecialchars($tgtYmdTo,   ENT_QUOTES, 'UTF-8');

// バリデーション関数
function validateJhSybt($jhSybt) {
    return preg_match('/^0[1-7]$/', $jhSybt);  // 01-07の整数のみ受け付ける
}
function validateTgtYmd($tgtYmd) {
    return preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $tgtYmd);  // YYYY/mm/ddのみ受け付ける
}

// バリデーション
if (!validateJhSybt($jhSybt)) {
    http_response_code(400);
    echo "400 Bad Request: パラメータが無効です。jhSybt は 01〜07 の2桁の整数文字列である必要があります。";
    exit;
}
if (!validateTgtYmd($tgtYmdFrom)) {
    http_response_code(400);
    echo "400 Bad Request: パラメータが無効です。tgtYmdFrom は YYYY/mm/dd 形式かつ / を %2F でエスケープした文字列である必要があります。";
    exit;
}
if (!validateTgtYmd($tgtYmdTo)) {
    http_response_code(400);
    echo "400 Bad Request: パラメータが無効です。tgtYmdTo は YYYY/mm/dd 形式かつ / を %2F でエスケープした文字列である必要があります。";
    exit;
}

// curlセッションでダウンロードを実施
$ch = curl_init(DOWNLOAD_URL."?jhSybt=".$jhSybt."&tgtYmdFrom=".$tgtYmdFrom."&tgtYmdTo=".$tgtYmdTo);
if (!$ch) {
    http_response_code(500);
    echo '500 Internal Server Error: 広域予備率Web公表システムへの接続に失敗しました。時間をおいて再度お試しください。';
    exit;
}
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // レスポンスボディを返り値とする
curl_setopt($ch, CURLOPT_USERAGENT, "PowerShell/7.3.8 Mozilla/5.0 (Windows NT 10.0; rv:105.0) Gecko/20100101 Firefox/105.0 (PowerShell)");
$contents = curl_exec($ch);  // アクセス実行
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);  // セッションを終了

// 正しくCSVが取得できたことを確認
if ((!$contents) || ($status_code != 200)) {
    http_response_code(500);
    echo "500 Internal Server Error: 広域予備率Web公表システムからのファイル取得に失敗しました。時間をおいて再度お試しください。";
    exit;
}

// データが存在しない場合はcsvではなくhtmlが返ってくる
if ($content_type != 'application/csv') {
    http_response_code(404);
    echo "404 Not Found: 対象とするデータがありません。";
    exit;
}

// UTF-8 with BOM で取得されるので、BOMを取り除く
$contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

// レスポンスを返す
header('Content-Type: text/csv');
echo $contents;

?>
