# 機能仕様（個別）: /provider/setup（初回のみ公開）

- GET: **初回のみ 200**（無副作用）/ 2回目以降は 302。
- POST: **作成→302**（login へ）。
- AUTH-REALM-SPLIT の原則を厳守。
