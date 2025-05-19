# grid-api

日本の電力系統情報を取得するためのAPIを趣味で開発しています。PHPを使って、電力広域的運営推進機関(OCCTO)または各一般送配電事業者のホームページからCSVファイルを取得しています。

## API仕様

### 共通仕様

#### エリアコード一覧表

|area|エリア名|
|--- |---|
|  1 |北海道|
|  2 |東北|
|  3 |東京|
|  4 |中部|
|  5 |北陸|
|  6 |関西|
|  7 |中国|
|  8 |四国|
|  9 |九州|
| 10 |沖縄|


### でんき予報

#### 概要

各一般送配電事業者が「でんき予報」サイトで公開している、電力使用状況データ（CSVデータ）を取得する。データ更新は5分に1回行われる。当日分のみ取得できる。

#### エンドポイント

```
https://powerflowmap.shikiblog.link/api/denkiyoho.php?area=xxx
```
- メソッド: `GET`
- クエリ:
  - `area`: 電力エリア. エリアコード一覧表に書かれた整数値のみ受け付ける

#### 実行例

東京エリアの、本日の電力使用状況CSVを取得したい：

```
https://powerflowmap.shikiblog.link/api/denkiyoho.php?area=3
```

#### レスポンス

UTF-8 エンコーディングの CSV を返す。

### 広域予備率情報

#### 概要

OCCTOが[広域予備率Web公表システム](https://web-kohyo.occto.or.jp/kks-web-public/)で公表している、広域予備率のCSVデータを取得する。データ形式は当該サイトの「情報ダウンロード」メニューから取得できるCSVデータと同一。

#### エンドポイント

```
https://powerflowmap.shikiblog.link/api/koikiyobiritsu.php?jhSybt=xxx&tgtYmdFrom=xxx&tgtYmdTo=xxx
```
- メソッド: `GET`
- クエリ:
  - `jhSybt`: ダウンロードしたいデータの種類
    - 広域予備率ブロック情報 週間:`01`
    - 広域予備率ブロック情報 翌々日:`05`
    - 広域予備率ブロック情報 翌日・当日:`02`
  - `tgtYmdFrom`: ダウンロード期間の開始日を指定する. YYYYmmdd の8桁で入力する
  - `tgtYmdTo`: ダウンロード期間の終了日を指定する
- 補足:
  - 情報種別ごとに、指定可能な期間は以下の通り
    - 週間: むこう1週間
    - 翌々日: 現在時刻が18時以降の場合、翌々日まで. 18時以前の場合、翌日まで
    - 翌日・当日: 現在時刻が18時以降の場合、翌日まで. 18時以前の場合、当日まで

#### 実行例

2025/4/1～2025/4/7の、週間の広域予備率情報を取得したい：

```
https://powerflowmap.shikiblog.link/api/koikiyobiritsu.php?jhSybt=01&tgtYmdFrom=20250401&tgtYmdTo=20250407
```

#### レスポンス

UTF-8 エンコーディングの CSVファイルを返す。

### 地内基幹送電線潮流実績

#### 概要

OCCTOが[系統情報サービス](https://occtonet3.occto.or.jp/public/dfw/RP11/OCCTO/SD/LOGIN_login)の「地内期間送電線潮流実績」で公開している、30分毎の地内基幹送電線潮流実績をCSVファイルで取得する。データ形式は当該サイトからダウンロードできるCSVファイルと同一。データ更新は30分に1回行われる。

一度取得したデータをサーバーに保存しておくことで、2回目以降はOCCTOシステムにアクセスすることなくCSVファイルを返せるように実装している。

「[基幹送電線潮流実績可視化サイト](https://github.com/thgcMtdh/powerflowmap)」のバックエンドとして機能している。

#### エンドポイント

```
https://powerflowmap.shikiblog.link/api/chinaiKikanJisseki.php?area=xxx&date=xxx
```
- メソッド: `GET`
- クエリ:
  - `area`: 電力エリア. エリアコード一覧表に書かれた整数値のみ受け付ける
  - `date`: ダウンロードしたい日付を指定する. YYYYmmdd の8桁で入力する. 未来の日付は指定できない。また、データ更新に1～2分のタイムラグがあるため、指定日当日の0:00～0:02は指定できない

#### 実行例

東京エリアの、2025/4/1の地内基幹送電線潮流実績を取得したい：

```
https://powerflowmap.shikiblog.link/api/chinaiKikanJisseki.php?area=3&date=20250401
```

#### レスポンス

UTF-8 エンコーディングの CSVファイルを返す。


## 開発者向け

### ファイル構成

すべて `api` フォルダ配下にまとめている。ウェブサーバに変更を反映する際は、サーバの `api` フォルダにPHPファイルをアップロードする

```
/ (document root)
├ api/
  ├ data/  ここに一度取得したデータを溜めておく
  |  └ chinaiKikanJisseki/
  |    ├ xxx.csv
  ├ chinaiKikanJisseki.php
  ├ xxx.php
  ├ ...
  
```

### 環境構築

PHPの開発環境が必要。筆者は Windows 環境で XAMPP + VSCode を導入している
