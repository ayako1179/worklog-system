# 勤怠管理アプリ

Laravelを使用した**勤怠管理システム**です。
従業員の出勤・退勤・休憩時間の記録および、勤怠修正申請の管理機能を備えています。

## 概要

本アプリは「coachtech」の模擬案件課題として開発されました。

一般ユーザーは出退勤や休憩を記録し、誤りがあった場合は修正申請を行うことができます。
管理者は、申請内容の確認と承認の操作が可能です。

認証には **Laravel Fortify**を使用しており、役割ごとに画面や機能を分離しています。
**管理者と一般ユーザーでルーティング・コントローラー・ビューを分ける構成**により、より実践的な権限設計を再現しています。
Docker 環境で動作するため、ローカルで起動・テストが可能です。

---

## 環境構築
DockerとLaravelの設定手順です。

### Dockerコンテナの構築

1. リポジトリをクローン
```bash
git clone git@github.com:ayako1179/item-market.git
cd item-market
```
2. DockerDesktopアプリを立ち上げる
3. コンテナをビルド・起動
```bash
docker-compose up -d --build
```

> *MacのM1・M2チップのPCの場合
ビルド時に以下のエラーが発生する場合があります：
`no matching manifest for linux/arm64/v8 in the manifest list entries`
対処法として、`docker-compose.yml`の`myspl`内に以下を追加してください。
```yaml
platform: linux/x86_64
# この行を追加
```

### Laravelアプリケーション構築
1. PHPコンテナに入る
```bash
docker-compose exec php bash
```
2. 依存関係をインストール
```bash
composer install
```
3. 環境ファイルを作成
「.env.example」ファイルを 「.env」ファイルに命名を変更。または、新しく.envファイルを作成
```bash
cp .env.example .env
```
4. .envに以下の環境変数を設定
``` text
APP_URL=http://localhost:8081
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

# Stripe APIキー（Stripe管理画面から取得）
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxx
```
5. アプリケーションキーの作成
``` bash
php artisan key:generate
```

6. マイグレーションの実行
``` bash
php artisan migrate
```

7. シーディングの実行
``` bash
php artisan db:seed
```
<!-- 8. ストレージのシンボリックリンクを作成
``` bash
php artisan storage:link
```
> Fortify と Stripe は `compser.json` に含まれているため、
追加インストールは不要です。（`composer install`で自動導入されます） -->

---

## 使用技術/実行環境
| 項目           | 内容                    |
| :------------- | :---------------------- |
| 言語           | PHP 8.1.33              |
| フレームワーク | Laravel 8.83.8          |
| データベース   | MySQL 8.0.26            |
| サーバー       | Nginx                   |
| コンテナ管理   | Docker / docker-compose |
| 認証           | Laravel Fortify         |
| <!--           | 決済                    | Stripe API（クレジットカード決済対応） | --> |

---

## 機能概要

本アプリで実装している主な機能は以下の通りです：

### 一般ユーザー機能
- ユーザー登録・ログイン・ログアウト（Fortify）
- 出勤・退勤時間の記録
- 休憩時間の登録
- 勤怠情報の閲覧
- 勤怠修正申請の作成・履歴閲覧

### 管理者機能
- ログイン（管理者アカウントのみ）
- 勤怠修正申請の一覧・詳細確認
- 修正申請の承認
- ユーザー情報の閲覧

<!-- - ユーザー登録・ログイン（Fortify） -->
<!-- - 商品出品・購入・Sold表示
- プロフィール編集（画像・住所・ユーザー名）
- いいね機能・コメント機能
- Stripeを利用したクレジットカード支払い決済（コンビニ払いはDB登録のみ） -->

---

## 画面一覧

###　一般ユーザー
- `/attendance` :勤怠打刻
- `/attendance/list` :勤怠一覧
- `/attendance/detail/{id}` :勤怠詳細
- `/stamp_correction_request/list` :申請一覧

### 管理者
- `/admin/attendance/list` :当日勤怠一覧
- `/admin/staff/list` :スタッフ一覧
- `/admin/attendance/staff/{id}` :スタッフ別 月次勤怠一覧
- `/stamp_correction_request/list` :申請一覧
- `/stamp_correction_request/approve/{attendance_correct_request_id}` :申請承認

---



---

## 認証機能（Laravel Fortify）
- 新規登録・ログイン・ログアウトを実装。
- Fortifyは `composer.json` に定義済みのため、手動導入不要。

---

## テスト環境構築と実行方法
本アプリでは主要機能を自動検証するためのテスト環境を整備しています。
`PHPUnit` により Feature / Unit テストを実行可能です。

---

### テスト用データベース設定
`.env.testing` を作成し、以下の設定を記述してください。
```text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=item_market_test
DB_USERNAME=root
DB_PASSWORD=root

# Stripeキー（必要に応じてコメントアウト）
# STRIPE_KEY=pk_test_************************
# STRIPE_SECRET=sk_test_************************
```

### テスト用データベースの作成
テスト環境実行前に、MySQLコンテナ内でテスト用DBを作成します。
```bash
# MySQLコンテナに接続
docker-compose exec mysql bash

# MySQLへログイン
mysql -u root -proot --skip-ssl

# テスト用データベースの作成
CREATE DATABASE `item_market_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### テスト実行手順
```bash
# PHPコンテナに入る
docker-compose exec php bash

# マイグレーションを実行（テスト用DB）
php artisan migrate --env=testing

# 全テストを実行
php artisan test

# 結果をファイルに出力（任意）
php artisan test > tests/results.txt
```

<!-- ### テスト結果（2025年10月時点）
```makefile
Tests:  39 passed
Time:   3.72s
```
> テストでは「商品出品・購入・コメント・認証」など主要機能を網羅的に検証しています。
`.env.testing` のDB（`item_market_test`）を使用するため、本番データは破壊されません。 -->

---

<!-- ## テストユーザー情報（動作確認用）
アプリケーションの動作確認に利用できる初期ユーザーです。
（PHPUnitテストで使用されるユーザーとは別データです）
| ユーザー名 | メールアドレス     | パスワード | 出品商品     |
| ---------- | ------------------ | ---------- | ------------ |
| testuser   | `test@example.com` | `12345678` | 腕時計・革靴 |

> `users` テーブルと `profiles` テーブルのシーディング時に自動作成されます。
ログイン後にプロフィール情報が紐づいていることを確認できます。 -->

---

## ER図
![alt](docs/er.png)

---

## URL一覧
- アプリケーション：http://localhost:8081/
- phpMyAdmin:：http://localhost:8080/

---

## 作者情報
作成者：Ayako  
GitHub：[https://github.com/ayako1179](https://github.com/ayako1179)


＊READMEの申し送り部分＊
勤怠詳細画面について（仕様説明）
勤怠詳細画面は 既存の勤怠レコードのみ表示されます
過去に出勤打刻されていない日付の勤怠レコードは存在しないため、
新規作成や当日の勤怠を後から追加することはできません
したがって、出張や打刻漏れ等で「勤怠レコードが存在しない場合」、
詳細画面への遷移は行わず、修正も行えません
これは実務の勤怠管理アプリに近い仕様です

📌 勤怠詳細画面について（仕様）
本アプリの勤怠詳細画面は、
既に存在する勤怠レコードのみ表示・編集できる仕様 となっています。
出張や打刻漏れなどで 過去に勤怠レコードが存在しない場合、
新規に勤怠レコードを作成する機能はありません。
そのため、
過去に出勤打刻していない日付
勤怠レコードが存在しない日付
初回出勤前の任意の日付
などについては、
勤怠詳細画面へ遷移することはできず、編集も行えません。
これは、実務の勤怠管理システムにおける
「打刻がなかった日は管理対象外」という仕様に準じています。

🟩 README の追加文（修正申請フロー）
修正申請（一般ユーザー）について
一般ユーザーが勤怠情報を修正したい場合は、
attendances テーブルを直接更新せず、
corrections テーブル・correction_breaks テーブルに
修正申請データを登録します。
修正申請は approval_status = pending（承認待ち） として保存されます。
attendance テーブルの status も pending に変更され、
管理者が閲覧・承認できる状態になります。
管理者が承認するまでは、勤怠情報は変更されません。
過去に勤怠レコードが存在しない日付に対しては
修正申請は行えません（詳細画面へ遷移できないため）。

### CSV出力機能について
- 管理者はスタッフ別勤怠一覧画面から CSV 出力が可能
- 対象月の勤怠情報のみを UTF-8 形式でダウンロード
- ルートは `/admin/attendance/staff/{id}` のまま使用

## 各画面の機能説明

### 一般ユーザー

#### ● 勤怠一覧 `/attendance/list`
- ログインユーザー自身の当月の勤怠を一覧表示
- 前月／翌月へ遷移可能
- 勤怠未登録日は空白表示
- 各日の「詳細」から勤怠詳細画面へ遷移

#### ● 勤怠詳細 `/attendance/detail/{id}`
- 当日の勤怠情報（出勤・退勤・休憩・合計）を表示
- 状態に応じて以下の動作を切替
  - 勤務外：出勤ボタン表示
  - 出勤中：休憩開始／退勤ボタン表示
  - 休憩中：休憩終了ボタン表示
  - 退勤済：表示のみ

#### ● 修正申請入力 `/attendance/fix/{id}`
- 出勤・退勤・休憩時間および備考を入力
- バリデーションエラー表示あり
- 申請送信後、確認画面を経由せず詳細へ戻る

#### ● 申請一覧 `/correction/list`
- 自分が送信した修正申請を表示
- タブで状態を切替（`承認待ち / 承認済み`）
- 承認待ちのみ詳細へ遷移可能

### 管理者

#### ● 当日勤怠一覧 `/admin/attendance/list`
- 当日の全ユーザーの勤怠を一覧表示
- 出勤・退勤・休憩・合計を確認可能
- 前日／翌日へ遷移可能
- 各行の「詳細」から勤怠詳細画面へ遷移

#### ● スタッフ一覧 `/admin/staff/list`
- 全一般ユーザーの氏名とメールアドレスを表示
- 各ユーザーの「詳細」から月別勤怠一覧へ遷移

#### ● スタッフ別 月次勤怠一覧 `/admin/attendance/staff/{id}`
- 対象スタッフの当月の勤怠を一覧表示
- 前月／翌月へ遷移可能
- 勤怠のない日は空白表示
- CSV出力ボタンから当月の勤怠をダウンロード

#### ● 修正申請一覧 `/stamp_correction_request/list`
- 全ユーザーの修正申請を表示
- タブで状態を切替（`承認待ち / 承認済み`）
- 承認待ちのみ承認画面へ遷移可能

#### ● 修正申請承認 `/stamp_correction_request/approve/{id}`
- 修正内容の確認表示（入力不可）
- 「承認」押下で以下を実施
  - 勤怠情報を修正内容で更新
  - 既存休憩を削除し申請内容で上書き
  - 申請ステータスを `approved` に更新
  - 承認済みはボタンが「承認済み」で disabled
