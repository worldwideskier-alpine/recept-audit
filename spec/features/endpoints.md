# features / endpoints（URL動作・早期ハンドラ・BNORM）

## 早見表（主要）
| Path | 要旨 | 応答 | 備考 |
|---|---|---|---|
| `/env` | FC 最上流で JSON 直返し | 200 JSON + no-store | require/include/autoload/session_start/ob_start 禁止 |
| `/env/` | `/env` と等価 | 200 JSON + no-store | 末尾スラ差で意味が変わらない |
| `/env-lite` | 物理ファイル直返し | 200 | `.htaccess` で -f 素通し |
| `/health-lite` | 物理ファイル直返し | 200 | 同上 |
| `/health` | **最小ブート（スキーマのみ）** | 200 JSON + no-store | seed/import 禁止 |
| `/` | ログインへ誘導 | 302 → `/login` + no-store | 301 禁止 |
| `/provider` | Provider 入口 | 302 → `/provider/login` + no-store | 末尾スラ任意 |
| `/provider/login` | Provider ログイン | 200 | |
| `/provider/setup` | 初期ユーザー作成（厳格ポリシー） | GET: 初回のみ200（無副作用） / POST: 作成→302 login | 二回目以降は GET/POST とも 302 |

BNORM（Directory‑relative Front Controller）により、サブディレクトリ配備でも同一動作を保証します。
