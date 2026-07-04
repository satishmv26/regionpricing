# SVExtensions_RegionPricing

Region-based price overrides for Adobe Commerce. Assign different prices per product per region without duplicating catalog entries.

**Version:** 1.0.0  
**Compatibility:** Adobe Commerce 2.4.4+ / Magento Open Source 2.4.4+  
**License:** Proprietary

---

## Overview

The module keeps a single shared catalog and overlays regional prices via an indexed `(product_id, region_id, price)` table. When a customer selects or is assigned a region, the product's base price is replaced with the regional override before any catalog rules, tier prices, special prices, or cart rules are applied.

### Business Flow

```
Store Visit → Login/Select Region → Resolve Region (Customer > Cookie > Default)
→ Fetch Regional Price → Apply Catalog Discounts → Apply Cart Rules/Coupons
→ Display Final Price → Add to Cart → Checkout → Order (prices frozen)
```

### Pricing Priority

1. Base Product Price
2. **Regional Price** (overrides base)
3. Catalog Price Rules
4. Customer Group / Tier Price
5. Special Price (date-range)
6. Cart Price Rules / Coupons
7. Tax & Shipping (at checkout)

---

## Installation

```bash
bin/magento module:enable SVExtensions_RegionPricing
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

---

## Configuration

Navigate to **Stores → Configuration → Catalog → Region Pricing**.

| Setting | Path | Description |
|---------|------|-------------|
| Enable Region Pricing | `sv_region_pricing/general/enabled` | Master toggle |
| Default Guest Region | `sv_region_pricing/general/default_region` | Fallback for logged-out visitors |
| Enable Logging | `sv_region_pricing/general/enable_logging` | Audit trail to `var/log/system.log` |

---

## Admin Panel

### Manage Regions

**Region Pricing → Manage Regions** (`svregion/region/index`)

Create regions with:
- **Name** — Display name (e.g., "North India")
- **Code** — Unique identifier (e.g., `IN-NORTH`, `US-WEST`)
- **Currency Code** — ISO 4217 currency (e.g., `INR`, `USD`)
- **Status** — Enable/disable

### Regional Prices

**Region Pricing → Regional Prices** (`svregion/regionprice/index`)

Assign a price override per product per region:
- **Product ID** — Magento product entity ID
- **Region** — Select from enabled regions
- **Price** — Override amount in base currency

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│  etc/di.xml                                                         │
│   ├── Plugin: Magento\Catalog\Model\Product\PricePlugin            │
│   │   (afterGetPrice / afterGetFinalPrice / afterGetMinimalPrice)  │
│   └── Plugin: Magento\Catalog\Block\Product\AbstractProduct         │
│       (inject region context into price rendering)                  │
├─────────────────────────────────────────────────────────────────────┤
│  Model/                                                             │
│   ├── RegionProvider.php     — Resolves region (Customer→Cookie→Def)│
│   ├── PriceResolver.php      — Looks up regional price override    │
│   ├── RegionPriceManagement.php — Public API delegate               │
│   └── RegionalPriceRepository.php — CRUD for sv_product_region_price│
├─────────────────────────────────────────────────────────────────────┤
│  Helper/                                                            │
│   ├── Logger.php  — Conditional audit logging                      │
│   └── Data.php    — Template helper for regional prices            │
├─────────────────────────────────────────────────────────────────────┤
│  Block/Selector.php + Controller/Frontend/SwitchRegion.php         │
│   — Region switcher in header, cookie-based persistence            │
├─────────────────────────────────────────────────────────────────────┤
│  Observer/Sales/OrderSaveObserver.php                               │
│   — Logs all item prices at order placement (immutable)            │
├─────────────────────────────────────────────────────────────────────┤
│  Api/ + etc/webapi.xml + etc/schema.graphqls                       │
│   — REST and GraphQL endpoints for price lookup                    │
└─────────────────────────────────────────────────────────────────────┘
```

### Region Resolution Order

1. **Customer attribute** `sv_region_code` — logged-in customers
2. **Cookie** `sv_region` — persisted browser selection
3. **URL parameter** `?sv_region=CODE` — switch on the fly
4. **Default region** — configured in admin for guests

---

## Database Schema

### `sv_region`

| Column | Type | Description |
|--------|------|-------------|
| `region_id` | int PK | Auto-increment |
| `name` | varchar(100) | Display name |
| `code` | varchar(20) UNIQUE | Region identifier |
| `currency_code` | char(3) | ISO currency |
| `status` | smallint | 0=disabled, 1=enabled |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `sv_product_region_price`

| Column | Type | Description |
|--------|------|-------------|
| `entity_id` | int PK | Auto-increment |
| `product_id` | int FK → catalog_product_entity | |
| `region_id` | int FK → sv_region | |
| `price` | decimal(12,4) | Override price |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Unique:** `(product_id, region_id)` — one price per product per region.

---

## APIs

### REST

```bash
# Public — no auth required
GET /rest/V1/products/{sku}/region-price?regionCode=IN-NORTH

# Admin — requires admin token
GET /rest/V1/sv-region/regions
GET /rest/V1/sv-region/regions/{regionId}
GET /rest/V1/sv-region/regions/code/{code}
GET /rest/V1/sv-region/prices
GET /rest/V1/sv-region/prices/{entityId}
```

**Response (public):**
```json
{
  "sku": "ABC123",
  "price": 1499.0000,
  "currency": "INR",
  "is_fallback": false
}
```

`is_fallback: true` means no override exists — the base catalog price will be used.

### GraphQL

```graphql
{
  products(filter: { sku: { eq: "ABC123" } }) {
    items {
      sku
      region_price(region: "IN-NORTH") {
        entity_id
        product_id
        region_id
        price
        sku
        region_code
        region_name
      }
    }
  }
}
```

```graphql
{
  svRegionPrices(regionCode: "IN-NORTH", sku: "ABC123") {
    entity_id
    product_id
    price
    sku
    region_code
  }
}
```

---

## Customer Attribute

The module creates a customer attribute `sv_region_code` (varchar) via setup patch. Admins can assign it in the customer edit form, and logged-in users see it in their account. When set, this attribute takes highest priority in region resolution.

---

## Acceptance Checklist

1. **Admin:** Create two enabled regions, assign different prices to a simple SKU and a configurable child SKU, reindex
2. **Storefront:** Region selector appears in header; switching updates prices on PDP, PLP, search results, mini-cart, cart, and checkout
3. **Edge cases:** No override → base price shows; disabled region → ignored; invalid code → graceful fallback
4. **Discount precedence:** Regional price + catalog rule + special price all compose correctly (regional replaces base, then standard Magento pricing chain applies)
5. **Order immutability:** Place an order, then change the regional price; the existing `sales_order_item` retains the original price
6. **APIs:** REST and GraphQL return correct values for direct SKU+region lookup
7. **Cache:** Varnish/FPC does not leak price between different region selections
8. **Performance:** Warmed price lookups complete in <300 ms
9. **Logs:** With logging enabled, region switches and order prices are recorded in `var/log/system.log`

---

## Troubleshooting

| Symptom | Check |
|---------|-------|
| Prices not changing | Is the module enabled in config? Is the region enabled? Run `bin/magento cache:flush` |
| Region selector not visible | Check that the block is in `header.panel` (default.xml) |
| API returns `is_fallback: true` | Confirm the region code matches exactly (case-insensitive) and a price is assigned to that product+region |
| Prices wrong after catalog rules | Regional price replaces base price **before** catalog rules — standard Magento pricing logic then applies on top |

---

## File Reference

| Path | Purpose |
|------|---------|
| `Api/Data/RegionalPriceInterface.php` | Regional price DTO contract |
| `Api/Data/RegionPriceInfoInterface.php` | Public API response DTO |
| `Api/RegionalPriceRepositoryInterface.php` | CRUD + lookup by product+region |
| `Api/RegionPriceManagementInterface.php` | Public SKU+region lookup |
| `Model/PriceResolver.php` | Core pricing engine |
| `Model/RegionProvider.php` | Region resolution logic |
| `Model/RegionPriceManagement.php` | Public API implementation |
| `Plugin/Catalog/Model/Product/PricePlugin.php` | Override product prices |
| `Plugin/Catalog/Block/PricePlugin.php` | Inject region context into blocks |
| `Observer/Sales/OrderSaveObserver.php` | Audit order prices |
| `Block/Selector.php` | Region switcher block |
| `Setup/Patch/Data/AddCustomerAttribute.php` | Customer attribute setup |
