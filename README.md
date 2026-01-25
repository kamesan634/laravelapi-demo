# 龜三的ERP Demo - Laravel API

![CI](https://github.com/kamesan634/laravelapi-demo/actions/workflows/ci.yml/badge.svg)

基於 PHP 8.2 + Laravel 11 DEMO用的零售業 ERP 系統後端 API。

## 技能樹 請點以下技能

| 技能 | 版本 | 說明 |
|------|------|------|
| PHP | 8.2 | 程式語言 |
| Laravel | 11 | 核心框架 |
| Eloquent ORM | 11 | ORM 框架 |
| Laravel Sanctum | 4.2 | API Token 認證 |
| MySQL | 8.4 | 資料庫 |
| Redis | 7 | 快取服務 |
| Laravel Migrations | - | 資料庫遷移 |
| Docker | - | 容器化佈署 |
| Nginx | Alpine | 網頁伺服器 |

## 功能模組

- **auth** - 認證管理（登入、登出、Token 刷新）
- **accounts** - 帳號管理（使用者、角色、門市、倉庫）
- **products** - 商品管理（商品、分類、單位、規格、條碼、組合商品）
- **suppliers** - 供應商管理（供應商、供應商報價）
- **customers** - 客戶管理（會員、會員等級、積分）
- **promotions** - 促銷管理（促銷活動）
- **sales** - 銷售管理（訂單、退貨、暫存單、發票、收銀班別）
- **inventory** - 庫存管理（庫存、庫存異動、入庫單、出庫單）
- **stock** - 庫存作業（盤點、調撥、調整）
- **purchasing** - 採購管理（採購單、驗收單、採購退貨）
- **audit** - 稽核日誌

## 快速開始

### 環境需求

- Docker & Docker Compose
- 或 PHP 8.2 + Composer + MySQL 8.4 + Redis

### 使用 Docker 佈署（推薦）

```bash
# 啟動所有服務
docker compose up -d

# 查看服務狀態
docker compose ps

# 執行資料庫遷移
docker compose exec app php artisan migrate

# 查看日誌
docker compose logs -f app

# 停止服務
docker compose down
```

### 本地開發

```bash
# 安裝依賴
composer install

# 複製環境設定
cp .env.example .env

# 產生應用程式金鑰
php artisan key:generate

# 執行資料庫遷移
php artisan migrate

# 啟動開發伺服器
php artisan serve
```

## Port

| 服務 | Port | 說明 |
|------|------|------|
| Nginx (API) | 8007 | REST API 服務 |
| MySQL | 3307 | 資料庫 |
| Redis | 6387 | 快取服務 |

## API 文件

API 端點列表：

```bash
# 查看所有路由
docker compose exec app php artisan route:list --path=api
```

主要端點：

| 模組 | 端點 | 說明 |
|------|------|------|
| 認證 | POST /api/auth/login | 登入 |
| 認證 | POST /api/auth/logout | 登出 |
| 認證 | GET /api/auth/me | 取得目前使用者 |
| 角色 | /api/roles | 角色 CRUD |
| 使用者 | /api/users | 使用者 CRUD |
| 門市 | /api/stores | 門市 CRUD |
| 倉庫 | /api/warehouses | 倉庫 CRUD |
| 分類 | /api/categories | 分類 CRUD |
| 商品 | /api/products | 商品 CRUD |
| 供應商 | /api/suppliers | 供應商 CRUD |
| 會員等級 | /api/customer-levels | 會員等級 CRUD |
| 會員 | /api/customers | 會員 CRUD |
| 促銷 | /api/promotions | 促銷活動 CRUD |
| 訂單 | /api/orders | 訂單 CRUD |
| 退貨 | /api/refunds | 退貨 CRUD |
| 收銀班別 | /api/cashier-shifts | 收銀班別管理 |
| 庫存 | /api/inventory | 庫存查詢 |
| 入庫單 | /api/goods-receipts | 入庫單 CRUD |
| 出庫單 | /api/goods-issues | 出庫單 CRUD |
| 盤點 | /api/stock-counts | 盤點單 CRUD |
| 調撥 | /api/stock-transfers | 調撥單 CRUD |
| 調整 | /api/stock-adjustments | 調整單 CRUD |
| 採購單 | /api/purchase-orders | 採購單 CRUD |
| 驗收單 | /api/purchase-receipts | 驗收單 CRUD |
| 採購退貨 | /api/purchase-returns | 採購退貨 CRUD |

## 測試資訊

### 測試帳號

執行 Seeder 後可使用以下測試帳號，密碼皆為：`password`

| 帳號 | 角色 | 說明 |
|------|------|------|
| admin@example.com | 系統管理員 | 擁有所有權限 |
| manager@example.com | 門市店長 | 門市管理權限 |
| cashier@example.com | 收銀員 | 收銀台操作權限 |

### 執行 Seeder

```bash
# 執行所有 Seeder
docker compose exec app php artisan db:seed

# 重置並重新執行
docker compose exec app php artisan migrate:fresh --seed
```

## 執行測試

```bash
# 執行所有測試
docker compose exec app php artisan test

# 執行特定測試
docker compose exec app php artisan test --filter=AuthTest
```

## API 使用範例

### 登入取得 Token

```bash
curl -X POST http://localhost:8007/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'
```

回應：
```json
{
  "message": "登入成功",
  "data": {
    "user": {
      "id": 1,
      "name": "系統管理員",
      "email": "admin@example.com"
    },
    "token": "1|abc123..."
  }
}
```

### 查詢商品列表

```bash
curl -X GET http://localhost:8007/api/products \
  -H "Authorization: Bearer {YOUR_TOKEN}"
```

### 建立訂單

```bash
curl -X POST http://localhost:8007/api/orders \
  -H "Authorization: Bearer {YOUR_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "store_id": 1,
    "customer_id": 1,
    "items": [
      {"product_id": 1, "quantity": 2, "unit_price": 199.00}
    ],
    "payments": [
      {"payment_method_id": 1, "amount": 398.00}
    ]
  }'
```

## 專案結構

```
laravelapi-demo/
├── docker-compose.yml          # Docker Compose 配置
├── Dockerfile                  # Docker 映像配置
├── docker/
│   ├── nginx/default.conf      # Nginx 配置
│   ├── mysql/my.cnf            # MySQL 配置
│   └── php/local.ini           # PHP 配置
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/    # API 控制器
│   │   └── Requests/           # 表單驗證請求
│   ├── Models/                 # Eloquent 模型
│   └── Traits/                 # 共用 Traits
├── database/
│   ├── migrations/             # 資料庫遷移
│   ├── factories/              # 模型工廠
│   └── seeders/                # 資料填充
├── routes/
│   └── api.php                 # API 路由
├── config/
│   └── sanctum.php             # Sanctum 配置
└── tests/                      # 測試
```

## 資料庫連線

### Docker 環境

- Host: `localhost`
- Port: `3307`
- Database: `laraveldemo_db`
- Username: `root`
- Password: `dev123`

```bash
# 使用 MySQL 客戶端連線
mysql -h localhost -P 3307 -uroot -pdev123 laraveldemo_db

# 或進入 Docker 容器
docker compose exec mysql mysql -uroot -pdev123 laraveldemo_db
```

## 健康檢查

```bash
# 檢查應用程式健康狀態
curl http://localhost:8007/up
```

## 常見問題

### Q: Docker 啟動失敗？

1. 確認 Docker 服務已啟動
2. 確認 Ports 8007, 3307, 6387 未被佔用
3. 查看日誌：`docker compose logs`

### Q: 登入失敗？

1. 確認使用正確的帳號密碼
2. 確認已執行 Seeder 建立測試帳號
3. 重置資料：`docker compose exec app php artisan migrate:fresh --seed`

### Q: API 回應 401？

1. 確認已登入並取得 Token
2. 確認 Authorization Header 格式正確：`Bearer {token}`
3. Token 可能已過期，請重新登入

### Q: 遷移失敗？

1. 確認 MySQL 容器已啟動且健康
2. 等待 MySQL 完全啟動後再執行遷移
3. 查看詳細錯誤：`docker compose exec app php artisan migrate --verbose`

## License

MIT License
