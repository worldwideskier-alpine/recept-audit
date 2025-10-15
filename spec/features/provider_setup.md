[GEN-TARGET]
id: provider-setup
outputs: [src/App/Controllers/ProviderSetupController.php]
acceptance: [evidence/runtime/PROVIDER_SETUP_OK.txt]
[/GEN-TARGET]

# features / provider_setup（/provider/setup の厳格ポリシー）

## 要件（MUST）
- **GET（初回のみ公開）**：`users=0` の**初回のみ** 200。**無副作用の HTML フォーム**（email, password）表示。HEAD も 200 + no-store。301 禁止。
- **POST（作成のみ）**：初期ユーザー作成時に `role='provider'`, `force_reset=1` を**必須**付与。成功後 **302 → /provider/login（no-store）**。
- **2回目以降（users>0）**：**GET/POST とも** 302 → `/provider/login`（no-store）。フォームは非公開。
- **UI**：公開画面から `/provider/setup` へのリンクは**アンカー禁止**（文言のみ）。`X-Robots-Tag: noindex, nofollow, noarchive` 推奨。

## 受入判定（E2E）
- A) `users=0` 環境で GET を連続2回：**200 + 無副作用**（`users` 件数不変）。
- B) POST 後、**1 行作成**・`force_reset=1`・**302 → /provider/login（no-store）**。
- C) `users>0` 環境で **GET/POST とも 302**（**301 が 0 件**）。
