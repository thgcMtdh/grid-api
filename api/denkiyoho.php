<?php

/**
 * 電力エリアを表すクラス
 */
class Area
{
    /** @var int 電力エリアを表す1～10の数値 */
    private $id;

    /**
     * @param int $id エリアの通し番号. 1～10の範囲の整数値を指定する
     */
    public function __construct($id) {
        // 整数でない場合は例外を発生させる
        if (!is_int($id) || $id < 1 || $id > 10) {
            throw new Exception('Invalid area id. $id should be integer between 1-10.');
        }
        $this->id = $id;
    }

    /**
     * @return int エリアの通し番号を、整数型で取得する
     */
    function id() {
        return $this->id;
    }

    /**
     * @return string エリアの通し番号を、"01"から"10"までの2桁の文字列で取得する
     */
    function asCode() {
        return str_pad($this->id, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @return string エリア名を漢字(北海道,東北,...)で取得する
     */
    function name() {
        switch ($this->id) {
            case 1: return "北海道";
            case 2: return "東北";
            case 3: return "東京";
            case 4: return "中部";
            case 5: return "北陸";
            case 6: return "関西";
            case 7: return "中国";
            case 8: return "四国";
            case 9: return "九州";
            case 10: return "沖縄";
        }
    }
}

/**
 * 本日のでんき予報のCSVダウンロードリンクを返す. 当日分のみ
 * @param \Area $area   電力エリア
 * @return string|bool  でんき予報データのURL. $area が不正な場合は false を返す
 */
function createCsvDownloadUrl($area) {
    // 現在日時をYYYYMMDD形式で表す
    $today = new DateTime();
    $datestr = $today->format('Ymd'); // YYYYMMDD形式の文字列

    // エリアによってリンクが異なるので、適切なリンクを返す
    switch ($area->id()) {
        case 1: // 北海道
            return "https://denkiyoho.hepco.co.jp/area/data/juyo_01_" . $datestr . ".csv";
        case 2: // 東北
            return "https://setsuden.nw.tohoku-epco.co.jp/common/demand/juyo_02_" . $datestr . ".csv";
        case 3: // 東京
            return "https://www.tepco.co.jp/forecast/html/images/juyo-d1-j.csv";
        case 4: // 中部
            return "https://powergrid.chuden.co.jp/denki_yoho_content_data/juyo_cepco003.csv";
        case 5: // 北陸
            return "https://www.rikuden.co.jp/nw/denki-yoho/csv/juyo_05_" . $datestr . ".csv";
        case 6: // 関西
            return "https://www.kansai-td.co.jp/yamasou/juyo1_kansai.csv";
        case 7: // 中国
            return "https://www.energia.co.jp/nw/jukyuu/sys/juyo_07_" . $datestr . ".csv";
        case 8: // 四国
            return "https://www.yonden.co.jp/nw/denkiyoho/juyo_shikoku.csv";
        case 9: // 九州
            return "https://www.kyuden.co.jp/td_power_usages/csv/juyo-hourly-" . $datestr . ".csv";
        case 10: // 沖縄
            return "https://www.okiden.co.jp/denki2/juyo_10_" . $datestr . ".csv";
        default:
            return false;
    }
}

/**
 * 指定したURLからコンテンツを取得する
 * @param string $url URL
 * @param array{string} $header リクエストヘッダー
 * @return string|bool 取得結果. エラーがあった場合はfalseを返す
 */
function curlContents($url, $header) {
    $ch = curl_init($url);  // cURLセッションを初期化
    if (!$ch) {
        return false;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);  // ヘッダーを追加
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // レスポンスボディを返り値とする
    $contents = curl_exec($ch);  // アクセス実行
    curl_close($ch);  // セッションを終了

    return $contents;
};

// バリデーション関数
function validateAreaQuery($area) {
    return filter_var($area, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
}

// CORS許可
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET");

// クエリパラメータを取得
$areaQuery = isset($_GET['area']) ? $_GET['area'] : null;

// エスケープ
$areaQuery = htmlspecialchars($areaQuery, ENT_QUOTES, 'UTF-8');

// バリデーション
if (!validateAreaQuery($areaQuery)) {
    http_response_code(400); // 400 Bad Request を設定
    echo "400 Bad Request: パラメータが無効です。area は 1〜10 の整数である必要があります。";
    exit;  // ここでスクリプトを終了
}

$area = new Area(intval($areaQuery));
$url = createCsvDownloadUrl($area);
$header = [];  // ヘッダーがあると、関西・中国・四国 のデータが取れなかった

$contents = curlContents($url, $header);
if (!$contents) {
    http_response_code(500);  // 500 Internal Server Error を設定
    header('Content-Type: text/plain');
    echo "500 Internal Server Error: TSOサイトからのファイル取得に失敗しました。";
    exit;  // ここでスクリプトを終了
}
$contents = mb_convert_encoding($contents, 'UTF-8', 'SJIS');

header('Content-Type: text/csv');
echo $contents;

?>
