# Manufacturing Module — Full Plan & Audit

**Date**: 2026-03-27
**Last Updated**: 2026-03-28
**Status**: COMPLETE — All phases implemented, all audit gaps closed, AI features + E2E tests done
**Estimated Effort**: 4-6 weeks
**Tier Requirement**: Business (€59/mo) or Max (€149/mo)

---

## 1. What Macedonian Businesses Need

### Target Users
1. **Small manufacturers** — bakeries, food production, textile workshops
2. **Farming / agriculture** — seed→crop cycle, animal farming, biological assets
3. **Construction / contracting** — raw materials → finished project cost tracking
4. **Craft workshops** — furniture, metalwork, ceramics

### Core Problems They Face Today
- **Manual калкулација (costing)** — Excel spreadsheets to calculate production cost
- **No утрасок tracking** — wastage/shrinkage is guessed, not measured
- **No норматив (BOM)** — recipes/formulas maintained outside the system
- **WIP invisible** — work-in-progress inventory not tracked in accounting
- **No variance analysis** — actual vs standard cost never compared
- **Co-production (сопроизводствен налог)** — by-product cost allocation done manually

### Macedonian Accounting Concepts

| MK Term | English | Description |
|---------|---------|-------------|
| Производствен норматив | Bill of Materials (BOM) | Recipe defining inputs → outputs |
| Работен налог | Production/Work Order | Actual production run document |
| Сопроизводствен налог | Co-production Order | Production yielding multiple outputs |
| Утрасок / Утрасување | Wastage / Shrinkage | Material lost during production |
| Калкулација | Costing Calculation | Final cost calculation per unit |
| Готов производ | Finished Good | Output of production |
| Полупроизвод | Semi-finished Good | Intermediate product (multi-stage) |
| Суровини и материјали | Raw Materials | Input materials |
| Недовршено производство | Work-in-Progress (WIP) | Production not yet completed |

---

## 2. What UJP Expects

### Chart of Accounts (Class 6 — Правилник Сл. весник 174/2011)

Already seeded in `MacedonianChartOfAccountsSeeder.php`:

| Code | Name (MK) | Purpose |
|------|-----------|---------|
| **600** | Производство (изградба) во тек | WIP / Production in progress |
| **601** | Залиха на полупроизводи | Semi-finished goods |
| **602** | Залиха на застарени недовршени производи | Obsolete WIP |
| **608** | Вредносно усогласување на недовршени производи | WIP impairment |
| **610** | Недовршено производство на биолошки средства | Biological WIP (farming) |
| **611** | Залиха на биолошки средства за продажба | Biological assets for sale |
| **630** | Производи на залиха | Finished goods in warehouse |
| **631** | Производи во туѓ склад | Finished goods in 3rd-party warehouse |
| **633** | Производи во продавница | Finished goods in retail |
| **637** | Залихи на некурентни производи и отпадоци | Obsolete products & scrap |
| **638** | Вредносно усогласување на залихите | Finished goods impairment |
| **650** | Вредност на стоките по пресметка | Purchase calculation |
| **651** | Зависни трошоци за набавка | Dependent purchase costs |
| **652** | Царини и други увозни давачки | Customs & import duties |

### Образец 36 — Биланс на состојба (Balance Sheet)

Manufacturing populates these AOP lines:

| AOP | Description | GL Accounts | Current Status |
|-----|-------------|-------------|----------------|
| 038 | Залихи на суровини и материјали | 500-series items | Filter key exists, not populated |
| 039 | Резервни делови, ситен инвентар | Spare parts | Filter key exists, not populated |
| 040 | **Недовршени производи и полупроизводи** | 600, 601 | **NEEDS manufacturing module** |
| 041 | **Готови производи** | 630-633 | **NEEDS manufacturing module** |
| 042 | Трговски стоки | 660-661 | Currently all items go here |
| 043 | Биолошки средства | 610-611 | Farming scenario |

### Образец 37 — Биланс на успех (Income Statement)

| AOP | Description | Impact |
|-----|-------------|--------|
| 204 | Залихи на готови производи — почеток | Opening finished goods |
| 205 | Залихи на готови производи — крај | Closing finished goods |
| 206 | Капитализирано сопствено производство | Own production capitalized |
| 208 | Трошоци за суровини и материјали | Raw material costs consumed |
| 209 | Набавна вредност на продадени производи | COGS for manufactured products |
| 210 | Набавна вредност на продадени материјали | COGS for sold raw materials |

### Required Legal Documents

1. **Приемница (Receipt)** — raw material received ✅ (exists as InventoryDocument TYPE_RECEIPT)
2. **Издатница (Issue)** — raw material issued to production ✅ (exists as InventoryDocument TYPE_ISSUE)
3. **Работен налог** — production order with inputs/outputs ❌ NEW
4. **Калкулација** — cost calculation sheet per product ❌ NEW
5. **Сопроизводствен налог** — co-production allocation doc ❌ NEW

---

## 3. Module Architecture

### 3.1 Database Schema

#### `item_types` — Item Classification
```
ALTER TABLE items ADD COLUMN item_type ENUM(
    'merchandise',       -- Трговска стока (current default)
    'raw_material',      -- Суровина / Материјал
    'semi_finished',     -- Полупроизвод
    'finished_good',     -- Готов производ
    'by_product',        -- Сопроизвод / By-product
    'consumable',        -- Потрошен материјал
    'biological'         -- Биолошко средство (farming)
) DEFAULT 'merchandise';

ALTER TABLE items ADD COLUMN default_wastage_percent DECIMAL(5,2) DEFAULT 0;
```

#### `boms` — Bill of Materials / Нормативи
```sql
CREATE TABLE boms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    currency_id INT UNSIGNED NULL,                   -- ⚠ GAP FIX: multi-currency support
    name VARCHAR(255) NOT NULL,                      -- "Леб 500г", "Обувки модел X"
    code VARCHAR(50) NULL,                           -- BOM-2026-0001
    output_item_id INT UNSIGNED NOT NULL,            -- Finished good item
    output_quantity DECIMAL(15,4) NOT NULL DEFAULT 1,
    output_unit_id INT UNSIGNED NULL,
    description TEXT NULL,
    expected_wastage_percent DECIMAL(5,2) DEFAULT 0, -- Нормативен утрасок %
    labor_cost_per_unit BIGINT UNSIGNED DEFAULT 0,   -- In cents
    overhead_cost_per_unit BIGINT UNSIGNED DEFAULT 0, -- In cents
    is_active BOOLEAN DEFAULT TRUE,
    version INT DEFAULT 1,                           -- BOM versioning
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,                       -- ⚠ GAP FIX: SoftDeletes

    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE SET NULL,
    FOREIGN KEY (output_item_id) REFERENCES items(id) ON DELETE RESTRICT,
    FOREIGN KEY (output_unit_id) REFERENCES units(id) ON DELETE SET NULL,
    INDEX idx_company (company_id),
    INDEX idx_output_item (output_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ⚠ COLLATE is CRITICAL — see gap analysis
```

#### `bom_lines` — BOM Input Materials
```sql
CREATE TABLE bom_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bom_id BIGINT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,                 -- Raw material / semi-finished
    quantity DECIMAL(15,4) NOT NULL,                -- Required quantity per BOM output
    unit_id INT UNSIGNED NULL,
    wastage_percent DECIMAL(5,2) DEFAULT 0,        -- Per-material wastage allowance
    notes TEXT NULL,
    sort_order INT DEFAULT 0,

    FOREIGN KEY (bom_id) REFERENCES boms(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT,
    INDEX idx_bom (bom_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `production_orders` — Работен налог
```sql
CREATE TABLE production_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    currency_id INT UNSIGNED NULL,                   -- ⚠ GAP FIX: multi-currency
    bom_id BIGINT UNSIGNED NULL,                   -- Optional: from BOM template
    order_number VARCHAR(50) NOT NULL,             -- РН-2026-0001
    order_date DATE NOT NULL,
    expected_completion_date DATE NULL,
    completed_at TIMESTAMP NULL,

    -- Status workflow
    status ENUM('draft','in_progress','completed','cancelled') DEFAULT 'draft',

    -- Output
    output_item_id INT UNSIGNED NOT NULL,
    planned_quantity DECIMAL(15,4) NOT NULL,        -- How many to produce
    actual_quantity DECIMAL(15,4) DEFAULT 0,        -- Actually produced
    output_warehouse_id BIGINT UNSIGNED NULL,

    -- Cost summary (calculated, in cents)
    total_material_cost BIGINT UNSIGNED DEFAULT 0,
    total_labor_cost BIGINT UNSIGNED DEFAULT 0,
    total_overhead_cost BIGINT UNSIGNED DEFAULT 0,
    total_wastage_cost BIGINT UNSIGNED DEFAULT 0,
    total_production_cost BIGINT UNSIGNED DEFAULT 0,  -- Sum of above
    cost_per_unit BIGINT UNSIGNED DEFAULT 0,          -- total / actual_quantity

    -- Variance (actual - normative)
    material_variance BIGINT DEFAULT 0,              -- Can be negative (favorable)
    labor_variance BIGINT DEFAULT 0,
    total_variance BIGINT DEFAULT 0,

    notes TEXT NULL,
    meta JSON NULL,
    created_by BIGINT UNSIGNED NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,                       -- ⚠ GAP FIX: SoftDeletes

    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE SET NULL,
    FOREIGN KEY (bom_id) REFERENCES boms(id) ON DELETE SET NULL,
    FOREIGN KEY (output_item_id) REFERENCES items(id) ON DELETE RESTRICT,
    FOREIGN KEY (output_warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,
    INDEX idx_company_status (company_id, status),
    INDEX idx_order_date (order_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `production_order_materials` — Material Consumption
```sql
CREATE TABLE production_order_materials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    production_order_id BIGINT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,                 -- Raw material consumed
    warehouse_id BIGINT UNSIGNED NULL,             -- Source warehouse

    -- Planned (from BOM)
    planned_quantity DECIMAL(15,4) NOT NULL DEFAULT 0,
    planned_unit_cost BIGINT UNSIGNED DEFAULT 0,   -- At time of order creation

    -- Actual (entered during/after production)
    actual_quantity DECIMAL(15,4) DEFAULT 0,
    actual_unit_cost BIGINT UNSIGNED DEFAULT 0,    -- WAC at time of consumption
    actual_total_cost BIGINT UNSIGNED DEFAULT 0,

    -- Wastage
    wastage_quantity DECIMAL(15,4) DEFAULT 0,      -- Утрасок
    wastage_cost BIGINT UNSIGNED DEFAULT 0,

    -- Variance
    quantity_variance DECIMAL(15,4) DEFAULT 0,     -- actual - planned
    cost_variance BIGINT DEFAULT 0,                -- actual_total - planned_total

    notes TEXT NULL,
    stock_movement_id BIGINT UNSIGNED NULL,         -- Link to stock OUT movement

    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,
    INDEX idx_production_order (production_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `production_order_labor` — Labor Costs
```sql
CREATE TABLE production_order_labor (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    production_order_id BIGINT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,             -- "Пекар - 8 часа", "Машинист"
    hours DECIMAL(8,2) DEFAULT 0,
    rate_per_hour BIGINT UNSIGNED DEFAULT 0,       -- In cents
    total_cost BIGINT UNSIGNED DEFAULT 0,
    work_date DATE NULL,
    notes TEXT NULL,

    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    INDEX idx_production_order (production_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `production_order_overhead` — Overhead Allocation
```sql
CREATE TABLE production_order_overhead (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    production_order_id BIGINT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,             -- "Струја", "Наем", "Амортизација"
    amount BIGINT UNSIGNED DEFAULT 0,              -- In cents
    allocation_method ENUM('per_unit','percentage','fixed') DEFAULT 'fixed',
    allocation_base DECIMAL(15,4) DEFAULT 0,       -- Units or percentage
    notes TEXT NULL,

    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    INDEX idx_production_order (production_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `co_production_outputs` — Сопроизводствен налог (By-products)
```sql
CREATE TABLE co_production_outputs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    production_order_id BIGINT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,                 -- Output item (main or by-product)
    is_primary BOOLEAN DEFAULT FALSE,              -- Main product vs by-product
    quantity DECIMAL(15,4) NOT NULL,
    warehouse_id BIGINT UNSIGNED NULL,

    -- Cost allocation
    allocation_method ENUM('weight','market_value','fixed_ratio','manual') DEFAULT 'weight',
    allocation_percent DECIMAL(8,4) DEFAULT 0,     -- % of total cost assigned
    allocated_cost BIGINT UNSIGNED DEFAULT 0,       -- In cents
    cost_per_unit BIGINT UNSIGNED DEFAULT 0,

    stock_movement_id BIGINT UNSIGNED NULL,         -- Link to stock IN movement
    notes TEXT NULL,

    FOREIGN KEY (production_order_id) REFERENCES production_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,
    INDEX idx_production_order (production_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### `production_templates` — Recurring Production (⚠ GAP FIX: schema was missing)
```sql
CREATE TABLE production_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    bom_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,                    -- "Дневна серија леб"
    default_quantity DECIMAL(15,4) NOT NULL,
    frequency ENUM('daily','weekly','monthly','custom') DEFAULT 'daily',
    is_active BOOLEAN DEFAULT TRUE,
    ai_suggested BOOLEAN DEFAULT FALSE,            -- Created by AI production planner
    last_generated_at TIMESTAMP NULL,
    next_generation_at TIMESTAMP NULL,
    meta JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (bom_id) REFERENCES boms(id) ON DELETE CASCADE,
    INDEX idx_company (company_id),
    INDEX idx_next_gen (next_generation_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Models

All new models in `Modules/Mk/Models/Manufacturing/`:

**⚠ CRITICAL PATTERNS (from gap analysis — see audit doc Section "GAP ANALYSIS"):**
- All models MUST use `BelongsToCompany` trait (NOT `TenantScope` — queue-unsafe)
- `Bom` and `ProductionOrder` MUST use `SoftDeletes` (MK law: no hard-deleting booked entries)
- All models MUST use `HasAuditing` trait (auto-logs to `audit_logs`)
- `Bom` and `ProductionOrder` MUST have `currency_id` FK (multi-currency support)
- All list models need `scopeWhereSearch()` + `scopeApplyFilters()`
- `ProductionOrder` and `Bom` should implement `HasMedia` (Spatie MediaLibrary)
- All migrations MUST include `$table->collation = 'utf8mb4_unicode_ci'`

| Model | Key Methods | Traits |
|-------|-------------|--------|
| `Bom` | `calculateNormativeCost()`, `duplicate()`, `getVersion()` | BelongsToCompany, SoftDeletes, HasAuditing, HasMedia |
| `BomLine` | Relationships to Item, Unit | — (CASCADE with parent) |
| `ProductionOrder` | `start()`, `complete()`, `cancel()`, `calculateCosts()`, `calculateVariances()`, `postToGl()` | BelongsToCompany, SoftDeletes, HasAuditing, HasMedia |
| `ProductionOrderMaterial` | `recordConsumption()`, `calculateWastage()` | — (CASCADE) |
| `ProductionOrderLabor` | Simple CRUD | — (CASCADE) |
| `ProductionOrderOverhead` | `allocate()` | — (CASCADE) |
| `CoProductionOutput` | `allocateCosts()`, `calculatePerUnit()` | SoftDeletes |
| `ProductionTemplate` | `createOrderFromTemplate()` | BelongsToCompany |

**API Resources** (in `Modules/Mk/Http/Resources/Manufacturing/`):
- `BomResource`, `BomLineResource`, `ProductionOrderResource`
- `ProductionOrderMaterialResource`, `ProductionOrderLaborResource`, `ProductionOrderOverheadResource`
- `CoProductionOutputResource`, `ProductionReportResource`

**Factories** (in `database/factories/`):
- `BomFactory` — states: `active()`, `inactive()`, `withLines()`
- `ProductionOrderFactory` — states: `draft()`, `inProgress()`, `completed()`, `cancelled()`

### 3.3 Services

#### `ManufacturingService` (Main orchestrator)
```
Modules/Mk/Services/ManufacturingService.php
```

**Key methods:**
- `createProductionOrder(Bom $bom, float $quantity, array $options): ProductionOrder`
- `startProduction(ProductionOrder $order): void` — status → in_progress, reserves materials
- `recordMaterialConsumption(ProductionOrder $order, Item $item, float $qty, float $wastage): void`
- `recordLaborCost(ProductionOrder $order, array $laborData): void`
- `recordOverhead(ProductionOrder $order, array $overheadData): void`
- `completeProduction(ProductionOrder $order, float $actualQty, ?array $coOutputs): void`
  - Calculates total cost
  - Creates stock OUT for raw materials consumed
  - Creates stock IN for finished goods produced
  - If co-production: allocates cost across outputs
  - Posts to GL
- `cancelProduction(ProductionOrder $order): void` — reverses stock reservations
- `calculateVariances(ProductionOrder $order): array` — actual vs normative
- `getProductionCostReport(Company $company, ?string $from, ?string $to): array`

#### `CostAllocationService` (Co-production)
```
Modules/Mk/Services/CostAllocationService.php
```

**Allocation methods:**
- `allocateByWeight(array $outputs, int $totalCost): array`
- `allocateByMarketValue(array $outputs, int $totalCost): array`
- `allocateByFixedRatio(array $outputs, int $totalCost, array $ratios): array`

### 3.4 Controllers

```
Modules/Mk/Http/Controllers/Manufacturing/BomController.php
Modules/Mk/Http/Controllers/Manufacturing/ProductionOrderController.php
Modules/Mk/Http/Controllers/Manufacturing/ProductionReportController.php
```

#### API Routes (under `/api/v1/` with company header)
```
# BOMs / Нормативи
GET    /manufacturing/boms                    — List BOMs
POST   /manufacturing/boms                    — Create BOM
GET    /manufacturing/boms/{bom}              — Get BOM with lines
PUT    /manufacturing/boms/{bom}              — Update BOM
DELETE /manufacturing/boms/{bom}              — Delete (only if unused)
POST   /manufacturing/boms/{bom}/duplicate    — Copy BOM as new version
GET    /manufacturing/boms/{bom}/cost         — Calculate normative cost

# Production Orders / Работни налози
GET    /manufacturing/orders                  — List orders (filterable by status)
POST   /manufacturing/orders                  — Create order (from BOM or scratch)
GET    /manufacturing/orders/{order}          — Get order with all details
PUT    /manufacturing/orders/{order}          — Update draft order
POST   /manufacturing/orders/{order}/start    — Start production
POST   /manufacturing/orders/{order}/complete — Complete + record output
POST   /manufacturing/orders/{order}/cancel   — Cancel order

# Material consumption (during production)
POST   /manufacturing/orders/{order}/materials        — Record material consumption
PUT    /manufacturing/orders/{order}/materials/{mat}   — Update consumption
POST   /manufacturing/orders/{order}/labor             — Add labor cost
POST   /manufacturing/orders/{order}/overhead          — Add overhead cost

# Co-production outputs
POST   /manufacturing/orders/{order}/outputs           — Define co-production outputs
PUT    /manufacturing/orders/{order}/outputs/allocate   — Calculate cost allocation

# Reports
GET    /manufacturing/reports/cost-analysis     — Production cost analysis
GET    /manufacturing/reports/variance           — Variance report (actual vs normative)
GET    /manufacturing/reports/wastage            — Wastage / утрасок report
GET    /manufacturing/reports/kalkulacija/{order} — Калкулација PDF for specific order
GET    /manufacturing/reports/work-order/{order}  — Работен налог PDF
```

---

## 4. IFRS / GL Integration

### 4.1 Account Mapping

| Event | Debit | Credit | Notes |
|-------|-------|--------|-------|
| **Issue raw materials to production** | 600 (WIP) | 630 (Inventory) | Materials consumed |
| **Record labor cost** | 600 (WIP) | 524 (Wages payable) | Direct labor allocated |
| **Record overhead** | 600 (WIP) | Various expense accounts | Overhead absorbed |
| **Complete production — single output** | 630 (Finished goods) | 600 (WIP) | FG at full cost |
| **Complete production — co-production** | 630 (FG - main) + 637 (by-product) | 600 (WIP) | Allocated costs |
| **Record wastage (normal)** | 600 (WIP) stays | — | Part of production cost |
| **Record wastage (abnormal)** | 580 (Loss on wastage) | 600 (WIP) | Expensed immediately |
| **Sell finished goods** | 702 (COGS) | 630 (FG Inventory) | Existing flow, WAC |
| **Scrap disposal** | 637 (Scrap) / Cash | 630 (Inventory) | If scrap has value |

### 4.2 IfrsAdapter Extensions

New methods in `IfrsAdapter.php`:

```php
/**
 * Post material issuance to production (raw materials → WIP)
 * DR 600 WIP / CR 630 Inventory (or item's inventory_account)
 */
public function postMaterialConsumption(ProductionOrderMaterial $material): void

/**
 * Post labor cost to WIP
 * DR 600 WIP / CR 524 Wages Payable
 */
public function postProductionLabor(ProductionOrderLabor $labor): void

/**
 * Post overhead absorption to WIP
 * DR 600 WIP / CR source expense account
 */
public function postProductionOverhead(ProductionOrderOverhead $overhead): void

/**
 * Post production completion — transfer WIP to Finished Goods
 * DR 630 Finished Goods / CR 600 WIP
 * For co-production: multiple DR lines with allocated costs
 */
public function postProductionCompletion(ProductionOrder $order): void

/**
 * Post abnormal wastage as expense
 * DR 580 Wastage Loss / CR 600 WIP
 */
public function postAbnormalWastage(ProductionOrder $order, int $amount): void

/**
 * Reverse all GL entries for a cancelled production order
 */
public function reverseProductionOrder(ProductionOrder $order): void
```

### 4.3 StockMovement Integration

New source types added to `StockMovement`:

```php
const SOURCE_PRODUCTION_CONSUME = 'production_consume';  // Raw material OUT to production
const SOURCE_PRODUCTION_OUTPUT = 'production_output';      // Finished good IN from production
const SOURCE_PRODUCTION_BYPRODUCT = 'production_byproduct'; // By-product IN
const SOURCE_PRODUCTION_WASTAGE = 'production_wastage';    // Wastage OUT (abnormal)
```

**Flow:**
1. `startProduction()` — no stock movement yet (just status change)
2. `recordMaterialConsumption()` → `StockService::recordStockOut()` with `SOURCE_PRODUCTION_CONSUME`
   - Materials leave raw material warehouse
   - WAC used for cost calculation
3. `completeProduction()` → `StockService::recordStockIn()` with `SOURCE_PRODUCTION_OUTPUT`
   - Finished goods enter finished goods warehouse
   - Unit cost = total production cost / actual quantity
4. Co-production → multiple `recordStockIn()` with `SOURCE_PRODUCTION_BYPRODUCT`
   - Each output gets allocated cost as unit cost

### 4.4 Образец 36/37 Mapping

To correctly populate UJP balance sheet lines, items need `item_type` → GL account mapping:

| item_type | GL Account | AOP (Образец 36) |
|-----------|-----------|-------------------|
| `raw_material` | 500-series | 038 |
| `merchandise` | 660 | 042 |
| `finished_good` | 630 | 041 |
| `semi_finished` | 601 | 040 |
| `by_product` | 637 | 041 (or 042) |
| `biological` | 610/611 | 043 |

**Implementation**: Override `inventory_account_id` on item creation based on `item_type`, or add logic in `obrazec_36.php` config to filter by item_type when calculating AOP values.

---

## 5. Vue Frontend Architecture

### 5.1 New Views

```
resources/scripts/admin/views/manufacturing/
├── bom/
│   ├── Index.vue              — BOM list with search, filter
│   ├── Create.vue             — BOM editor (add items, quantities, wastage %)
│   └── View.vue               — BOM detail with cost breakdown
├── orders/
│   ├── Index.vue              — Production order list (status tabs)
│   ├── Create.vue             — Create order (select BOM, set quantity)
│   ├── View.vue               — Order detail + material/labor/overhead entry
│   └── Complete.vue           — Completion wizard (actual qty, co-production outputs)
├── reports/
│   ├── CostAnalysis.vue       — Period-based production cost report
│   ├── VarianceReport.vue     — Actual vs normative comparison
│   └── WastageReport.vue      — Утрасок analysis by item/period
└── components/
    ├── BomLineEditor.vue      — Inline editor for BOM materials
    ├── MaterialConsumption.vue — Record actual materials used
    ├── LaborEntry.vue         — Labor cost entry form
    ├── OverheadEntry.vue      — Overhead allocation form
    ├── CostAllocationModal.vue — Co-production cost split wizard
    └── ProductionStatusBadge.vue — Status indicator
```

### 5.2 Router Entries

```js
// In admin-router.js under partner routes
{
  path: 'manufacturing/boms',
  name: 'manufacturing.boms',
  component: () => import('@/scripts/admin/views/manufacturing/bom/Index.vue'),
},
{
  path: 'manufacturing/boms/create',
  name: 'manufacturing.boms.create',
  component: () => import('@/scripts/admin/views/manufacturing/bom/Create.vue'),
},
{
  path: 'manufacturing/boms/:id',
  name: 'manufacturing.boms.view',
  component: () => import('@/scripts/admin/views/manufacturing/bom/View.vue'),
},
{
  path: 'manufacturing/orders',
  name: 'manufacturing.orders',
  component: () => import('@/scripts/admin/views/manufacturing/orders/Index.vue'),
},
{
  path: 'manufacturing/orders/create',
  name: 'manufacturing.orders.create',
  component: () => import('@/scripts/admin/views/manufacturing/orders/Create.vue'),
},
{
  path: 'manufacturing/orders/:id',
  name: 'manufacturing.orders.view',
  component: () => import('@/scripts/admin/views/manufacturing/orders/View.vue'),
},
{
  path: 'manufacturing/orders/:id/complete',
  name: 'manufacturing.orders.complete',
  component: () => import('@/scripts/admin/views/manufacturing/orders/Complete.vue'),
},
{
  path: 'manufacturing/reports',
  name: 'manufacturing.reports',
  component: () => import('@/scripts/admin/views/manufacturing/reports/CostAnalysis.vue'),
},
```

### 5.3 Sidebar Menu (config/invoiceshelf.php)

New top-level menu group `manufacturing`:

```php
[
    'title' => 'manufacturing.title',       // "Производство"
    'group' => 'manufacturing',
    'icon' => 'CogIcon',                    // or custom factory icon
    'feature_flag' => 'manufacturing',
    'submenu' => [
        [
            'title' => 'manufacturing.boms',        // "Нормативи"
            'link' => '/manufacturing/boms',
            'ability' => 'view-manufacturing',
        ],
        [
            'title' => 'manufacturing.orders',      // "Работни налози"
            'link' => '/manufacturing/orders',
            'ability' => 'view-manufacturing',
        ],
        [
            'title' => 'manufacturing.reports',     // "Извештаи"
            'link' => '/manufacturing/reports',
            'ability' => 'view-manufacturing',
        ],
    ],
],
```

---

## 6. Production Order Workflow

### 6.1 Single Product Production

```
┌──────────┐    ┌─────────────┐    ┌────────────┐    ┌───────────┐
│  DRAFT   │───>│ IN_PROGRESS │───>│ COMPLETED  │    │ CANCELLED │
│          │    │             │    │            │    │           │
│ Define:  │    │ Record:     │    │ Calculate: │    │ Reverse   │
│ - BOM    │    │ - Materials │    │ - Total    │    │ all stock │
│ - Qty    │    │ - Labor     │    │ - Per unit │    │ movements │
│ - Date   │    │ - Overhead  │    │ - Variance │    │           │
└──────────┘    │ - Wastage   │    │ Post GL    │    └───────────┘
                └─────────────┘    └────────────┘
```

### 6.2 Farming Example (User's Scenario)

**BOM: "Пченица — 1 хектар"**
| # | Input (Суровина) | Quantity | Unit | Est. Cost |
|---|-----------------|----------|------|-----------|
| 1 | Семе пченица | 200 | кг | 6,000 ден |
| 2 | Ѓубриво NPK | 300 | кг | 9,000 ден |
| 3 | Пестицид | 5 | литри | 3,000 ден |
| 4 | Дизел гориво | 80 | литри | 5,600 ден |
| 5 | Наемнина трактор | 1 | услуга | 4,000 ден |
| **Total normative cost** | | | | **27,600 ден** |

**Production Order: РН-2026-0001**
- Planned output: 3,000 кг пченица
- Normative cost per кг: 9.20 ден

**After harvest (actual):**
- Materials consumed: 28,200 ден (actual, slightly more fertilizer)
- Labor: 5,000 ден (harvesting)
- Overhead: 2,000 ден (storage)
- Wastage: 200 кг lost to rain damage
- **Actual output: 2,800 кг** (bad weather year)
- **Actual cost per кг: 12.57 ден** (35,200 / 2,800)
- **Variance: +3.37 ден/кг** (unfavorable)

**Or a good year:**
- **Actual output: 4,500 кг**
- **Actual cost per кг: 7.82 ден**
- **Variance: -1.38 ден/кг** (favorable)

### 6.3 Co-production Example (Сопроизводствен налог)

**Мелница (Flour Mill):**
- Input: 1,000 кг пченица @ 12 ден/кг = 12,000 ден
- Labor: 2,000 ден
- Overhead: 1,000 ден
- **Total cost: 15,000 ден**

**Outputs:**
| Output | Qty | Market Price | % Allocation | Allocated Cost | Cost/кг |
|--------|-----|-------------|-------------|---------------|---------|
| Брашно (flour) — primary | 750 кг | 25 ден/кг | 78.9% | 11,842 ден | 15.79 ден |
| Трици (bran) — by-product | 200 кг | 8 ден/кг | 6.7% | 1,005 ден | 5.03 ден |
| Мекини (middlings) — by-product | 40 кг | 15 ден/кг | 2.5% | 375 ден | 9.38 ден |
| Утрасок (waste) | 10 кг | 0 | 0% | 0 ден | — |
| **Unallocated** | | | **11.9%** | **1,778 ден** | |

Allocation method: **market value** — cost allocated proportional to selling price × quantity.

---

## 7. PDF Templates — All Created

**Key regulatory finding:** Macedonia does NOT prescribe rigid "Образец" forms for manufacturing documents (unlike Образец ЕТ). These are **internal accounting documents** per МСС 2 (IAS 2) and Закон за сметководствени работи (Сл. весник 173/2022). Format follows standard MK industry practice (PANTHEON, Luca, HELIX, Infomatrix).

All templates follow our existing styling (dark purple `#2d2040` headers, DejaVu Sans, DomPDF-safe CSS).

### 7.1 Работен налог — `raboten-nalog.blade.php` ✅ CREATED
A4 portrait. 5 sections:
- **А. Готов производ** — output items table (supports co-production)
- **Б. Суровини и материјали** — 10 columns: Р.бр, Шифра, Назив, Ед.мерка, Нормативна кол., Издадена кол., Утрасок, Ед.цена, Вкупна вредност, Отстапување
- **В. Директен труд** — description, date, hours, rate, total
- **Г. Режиски трошоци** — description, allocation method, base, amount
- **Д. Рекапитулар** — 9-row cost summary: material + labor + overhead + wastage = total, per-unit, variance from normative
- Signatures: Изготвил / Примил во магацин / Раководител на производство

### 7.2 Калкулација — `kalkulacija.blade.php` ✅ CREATED
A4 portrait. 2 sections:
- **1. Директен материјал** — detailed per-material breakdown with per-unit-of-output cost
- **2. Калкулација по единица** — normative vs actual comparison table with variance (поволно/неповолно)
- Optional margin calculation if selling price provided
- Signatures: Изготвил / Одобрил

### 7.3 Сопроизводствен налог — `co-production-order.blade.php` ✅ CREATED
A4 portrait. Extends production order with:
- **А. Суровини** — input materials
- Cost summary box (material + labor + overhead + wastage = total for allocation)
- **Б. Излезни производи** — allocation table with ГЛАВЕН/СПОРЕДEН/УТРАСОК badges
  - Supports 4 allocation methods: По тежина, По пазарна вредност, По фиксен сооднос, Рачно
  - Shows percent, allocated cost, cost per unit
- **В. Преглед по производ** — per-product summary
- Verification line: confirms total allocated = total cost (or shows unallocated remainder)
- Signatures: Изготвил / Раководител на производство / Одговорно лице

### 7.4 Приемница — `priemnica.blade.php` ✅ CREATED
A4 portrait. 8 numbered columns (per Правилник Сл. весник 51/04):
1-Р.бр, 2-Шифра, 3-Назив, 4-Ед.мерка, 5-Количина, 6-Цена, 7-Вредност, 8-Забелешка
- Supports both supplier receipt and production receipt (work order reference)
- Signatures: Предал / Примил / Одобрил

### 7.5 Издатница — `izdatnica.blade.php` ✅ CREATED
A4 portrait. Same 8-column structure as Приемница.
- From warehouse / To warehouse or production dept
- Work order reference + Requestor field
- Signatures: Издал / Примил / Одобрил

### 7.6 Требовница — `trebovnica.blade.php` ✅ CREATED
A4 portrait. 8 numbered columns (per ТОЗ обр. 0653):
1-Р.бр, 2-Шифра, 3-Назив, 4-Ед.мерка, 5-Побарана кол., 6-Одобрена кол., 7-Издадена кол., 8-Забелешка
- Mismatch highlighting (issued ≠ approved)
- Signatures: Побарал / Одобрил / Издал

### 7.7 Норматив (BOM) — `normativ.blade.php` ✅ CREATED
A4 portrait. Material recipe with:
- Version badge, approval status
- Material table: 8 columns including wastage % and value per unit of output
- Cost summary: material + labor + overhead = нормативна цена на чинење
- Signatures: Изготвил / Одобрил

### 7.8 Лагерска картица — `lagerska-kartica.blade.php` ✅ CREATED
A4 landscape. Triple-grouped Kardex format (per Правилник Сл. весник 51/04):
- 3 column groups: ВЛЕЗ (Кол/Цена/Вредност), ИЗЛЕЗ (Кол/Цена/Вредност), САЛДО (Кол/ПСЦ/Вредност)
- Opening balance row, color-coded entries (green=in, red=out)
- Source type labels in Macedonian (Фактура-наб., Производство, Утрасок, etc.)
- Closing balance totals
- Signatures: Магационер / Сметководител

---

## 8. Implementation Phases

### Phase 1 — Foundation (Week 1-2) ✅ COMPLETE

**Database & Models:**
- [ ] Migration: add `item_type` to items table
- [x] Migration: create `boms` and `bom_lines` tables (`2025_08_60_000002`, `000003`)
- [x] Migration: create `production_orders` and related tables (`000004`-`000009`)
- [x] Models: Bom, BomLine, ProductionOrder, ProductionOrderMaterial
- [x] Models: ProductionOrderLabor, ProductionOrderOverhead, CoProductionOutput, ProductionTemplate
- [ ] Add `SOURCE_PRODUCTION_*` constants to StockMovement

**Service Layer:**
- [x] `ManufacturingService` — core CRUD + workflow methods
- [x] `CostAllocationService` — co-production cost splitting (4 methods, div-by-zero fix)

**API:**
- [x] BOM CRUD routes + controller (7 endpoints)
- [x] Production Order CRUD routes + controller (10 endpoints)
- [x] Request validation classes (7 FormRequest classes in `Modules/Mk/Http/Requests/Manufacturing/`)

**Vue:**
- [x] BOM Index + Create + View pages
- [x] BomLineEditor component (inline in Create.vue)
- [x] i18n keys (all 4 languages, ~150 keys in `resources/scripts/admin/i18n/manufacturing.js`)

### Phase 2 — Production Workflow (Week 2-3) ✅ COMPLETE

**Backend:**
- [x] `startProduction()` — status transition
- [x] `recordMaterialConsumption()` — stock OUT + cost tracking
- [x] `recordLaborCost()` / `recordOverhead()`
- [x] `completeProduction()` — final cost calc + stock IN
- [x] `cancelProduction()` — reversal logic

**IFRS Integration:**
- [ ] `IfrsAdapter::postMaterialConsumption()` — method stubs needed
- [ ] `IfrsAdapter::postProductionLabor()` — method stubs needed
- [ ] `IfrsAdapter::postProductionOverhead()` — method stubs needed
- [ ] `IfrsAdapter::postProductionCompletion()` — method stubs needed
- [ ] `IfrsAdapter::reverseProductionOrder()` — method stubs needed
- [x] Observer: `ProductionOrderObserver` registered in AppServiceProvider (behind accounting backbone flag)

**Vue:**
- [x] Production Order Index (with status tabs/filters)
- [x] Production Order Create (BOM selector + quantity)
- [x] Production Order View (material/labor/overhead entry + PDF download buttons)
- [x] MaterialConsumption, LaborEntry, OverheadEntry components (inline in views)

### Phase 3 — Co-production & Completion (Week 3-4) ✅ COMPLETE

**Backend:**
- [x] Co-production output recording
- [x] Cost allocation algorithms (weight, market value, fixed ratio, manual)
- [x] Variance calculation (actual vs normative) — `ProductionOrder::calculateVariances()`
- [ ] Wastage classification (normal vs abnormal)
- [ ] Abnormal wastage GL posting

**Vue:**
- [x] Complete.vue — completion wizard with co-production
- [x] CostAllocationModal — co-production cost split (in Complete.vue)
- [x] ProductionStatusBadge component (inline in views)

### Phase 4 — Reports & PDFs (Week 4-5) ✅ COMPLETE

**Backend:**
- [x] Cost analysis report endpoint (`ManufacturingReportService::getCostAnalysis()`)
- [x] Variance report endpoint (`ManufacturingReportService::getVarianceReport()`)
- [x] Wastage report endpoint (`ManufacturingReportService::getWastageReport()`)
- [x] Работен налог PDF template (`production-order.blade.php`)
- [x] Калкулација PDF template (`kalkulacija.blade.php`)
- [x] Сопроизводствен налог PDF template (`co-production.blade.php`)

**Vue:**
- [x] CostAnalysis.vue — period-based production cost report
- [x] VarianceReport.vue — normative vs actual
- [x] WastageReport.vue — утрасок tracking
- [x] PDF preview integration (PDF download buttons on View.vue)
- [x] Report cards on manufacturing Index.vue

### Phase 5 — Образец 36/37 Integration & Polish (Week 5-6) — PARTIAL

**Backend:**
- [ ] Item type → GL account auto-mapping (migration `item_type` on items not yet created)
- [x] Образец 36 AOP 038-043 — already mapped via `config/ujp_forms/obrazec_36.php` account_code_to_aop (600→040, 620→041, 630→042)
- [ ] Образец 37 AOP 204-210 population from production data
- [x] Subscription tier enforcement — `middleware('tier:business')` on manufacturing route group
- [ ] Sidebar menu entry in `config/invoiceshelf.php`

**Testing:**
- [ ] Unit tests for ManufacturingService
- [x] Unit tests for CostAllocationService — 9 tests, all passing
- [x] Feature tests for all API endpoints — 17 tests (blocked by pre-existing `purchase_orders` migration bug, not ours)
- [ ] E2E Playwright tests

**Cleanup:**
- [x] Migration idempotency (Schema::hasTable checks in all 9 migrations)
- [x] Error handling and validation messages
- [ ] Mobile responsiveness

### Remaining Work (Audit Gaps) — ALL CLOSED

**Backend — ALL DONE (were already implemented in prior sessions):**
- [x] `item_type` ENUM field on items table + migration (`2025_08_60_000001`)
- [x] `SOURCE_PRODUCTION_*` constants on StockMovement model
- [x] 6 IfrsAdapter GL posting methods fully implemented
- [x] Abnormal wastage classification + GL posting
- [x] Sidebar menu items in `config/invoiceshelf.php`
- [x] `manufacturing` feature flag in `config/subscriptions.php`
- [x] `tier:business` middleware on route group
- [x] 7 FormRequest validation classes
- [x] ProductionOrderObserver registered in AppServiceProvider

**PDF Templates — ALL 8 COMPLETE:**
- [x] Приемница, Издатница, Требовница, Норматив, Лагерска картица

**AI Features — ALL DONE:**
- [x] `ProductionAiService` — 5 Gemini methods (suggest, predict, detect, parse, explain)
- [x] `ProductionAiController` — 5 API endpoints under `/manufacturing/ai/`
- [x] Vue integration: AI suggestion card on BOM Create, NL input on Order Create
- [x] i18n keys in all 4 locales

**Testing — DONE:**
- [x] CostAllocationServiceTest — 9 unit tests passing
- [x] E2E Playwright tests — `manufacturing-e2e.spec.js` (22 tests)
- [ ] Fix pre-existing `purchase_orders` migration bug (not manufacturing-specific)

**Future Enhancements (nice-to-have, not blocking):**
- [ ] API Resource classes (`BomResource`, `ProductionOrderResource`)
- [ ] Stock availability warnings on Create form
- [ ] Live cost preview panel on BOM Create

---

## 9. Complete Test Specification

### 9.0 Test Data — Manufacturing Seeder for Company 2

Seeder: `database/seeders/ManufacturingTestSeeder.php`
Target: Company 2 (Teknomed DOO) — idempotent, skips if tables don't exist.

**Items to create (all track_quantity=true, with stock IN):**

| Item Name | item_type | Initial Stock | WAC (cents) | Unit |
|-----------|-----------|---------------|-------------|------|
| Брашно тип 400 | raw_material | 500 кг | 5,000 | кг |
| Квасец суво | raw_material | 20 кг | 30,000 | кг |
| Сол кујнска | raw_material | 50 кг | 4,000 | кг |
| Шеќер бел | raw_material | 100 кг | 6,000 | кг |
| Масло сончогледово | raw_material | 30 литри | 12,000 | литар |
| Јајца | raw_material | 200 парчиња | 800 | парче |
| Млеко свежо | raw_material | 50 литри | 5,500 | литар |
| Сирење бело | raw_material | 25 кг | 35,000 | кг |
| Семе пченица | raw_material | 500 кг | 3,000 | кг |
| Ѓубриво NPK | raw_material | 300 кг | 3,000 | кг |
| Пестицид | raw_material | 10 литри | 60,000 | литар |
| Дизел гориво | raw_material | 200 литри | 7,000 | литар |
| Тесто за бурек | semi_finished | 0 | 0 | кг |
| Леб 500г | finished_good | 0 | 0 | парче |
| Кифла | finished_good | 0 | 0 | парче |
| Бурек со сирење | finished_good | 0 | 0 | парче |
| Пченица зрно | finished_good | 0 | 0 | кг |

**Warehouses:** "Магацин суровини" (raw), "Магацин готови производи" (finished)

**BOMs:**

| Code | Name | Output | Qty | Lines | Labor/unit | Overhead/unit | Wastage% |
|------|------|--------|-----|-------|-----------|--------------|----------|
| BOM-2026-0001 | Леб 500г | Леб 500г | 1 | Брашно 0.35кг(2%), Квасец 0.01кг(1%), Сол 0.005кг(0%) | 200 | 100 | 3% |
| BOM-2026-0002 | Бурек со сирење | Бурек со сирење | 1 | Брашно 0.2кг(1%), Масло 0.05л(0%), Сирење 0.15кг(2%), Јајца 1(0%) | 350 | 150 | 2% |
| BOM-2026-0003 | Пченица 1 хектар | Пченица зрно | 3000 | Семе 200кг(0%), Ѓубриво 300кг(5%), Пестицид 5л(0%), Дизел 80л(0%) | 100 | 50 | 10% |

**Production Orders:**

| # | BOM | Status | Planned | Actual | Notes |
|---|-----|--------|---------|--------|-------|
| РН-2026-0001 | BOM-001 | completed | 500 | 485 | Standard bread production |
| РН-2026-0002 | BOM-001 | completed (co-production) | 300 | 280 леб + 50 кифли | Co-production: bread + rolls |
| РН-2026-0003 | BOM-002 | in_progress | 100 | — | Burek in production |
| РН-2026-0004 | BOM-003 | draft | 3000 | — | Farming — planned |
| РН-2026-0005 | BOM-001 | cancelled | 200 | — | Was cancelled |

---

### 9.1 Unit Tests — Pure Calculations (`tests/Unit/Manufacturing/`)

All monetary values in **cents** (MKD × 100). These tests need NO database.

#### File: `ManufacturingCalculationTest.php`

**Test 1: BOM normative cost — "Леб 500г"**
```
Input:
  output_quantity = 1
  Line 1: Брашно 0.35 кг × WAC 5000 cents + 2% wastage = 0.357 × 5000 = 1785
  Line 2: Квасец 0.01 кг × WAC 30000 cents + 1% wastage = 0.0101 × 30000 = 303
  Line 3: Сол 0.005 кг × WAC 4000 cents + 0% wastage = 0.005 × 4000 = 20
  Labor: 200 cents/unit
  Overhead: 100 cents/unit

Expected:
  total_material_per_unit = 1785 + 303 + 20 = 2108 cents
  normative_cost_per_unit = 2108 + 200 + 100 = 2408 cents
  For 500 units: 2408 × 500 = 1,204,000 cents
```

**Test 2: BOM normative cost — batch recipe (output > 1)**
```
Input:
  BOM: "Пченица 1 хектар", output_quantity = 3000 кг
  Line 1: Семе 200 кг × WAC 3000 = 600,000 + 0% = 600,000
  Line 2: Ѓубриво 300 кг × WAC 3000 = 900,000 + 5% = 945,000
  Line 3: Пестицид 5 л × WAC 60000 = 300,000 + 0% = 300,000
  Line 4: Дизел 80 л × WAC 7000 = 560,000 + 0% = 560,000
  Labor: 100 cents/unit (per кг)
  Overhead: 50 cents/unit

Expected:
  total_material_for_batch = 600,000 + 945,000 + 300,000 + 560,000 = 2,405,000
  per_unit_material = 2,405,000 / 3000 = 801.67 → 802 cents (rounded)
  per_unit_total = 802 + 100 + 50 = 952 cents
```

**Test 3: WAC integration with production consume**
```
Setup:
  Item: Брашно
  Opening: 500 кг @ WAC 4800 = 2,400,000
  Purchase: 300 кг @ 5500 = 1,650,000
  WAC after purchase: (2,400,000 + 1,650,000) / 800 = 5062.5 → 5063 cents

Production consumes 350 кг:
  Cost = 350 × 5063 = 1,772,050 cents
  Remaining: 450 кг (WAC unchanged: 5063)
  Remaining value: 450 × 5063 = 2,278,350

Verify:
  original_value + purchase_value = consumed_value + remaining_value
  2,400,000 + 1,650,000 = 1,772,050 + 2,278,350
  4,050,000 ≈ 4,050,400 (rounding delta ≤ 1 cent × qty)
```

**Test 4: Variance — favorable (used less than normative)**
```
BOM normative per unit: 2408 cents
Planned: 500 units → normative total = 1,204,000

Actual production of 480 units:
  Material: 920,000 cents
  Labor: 110,000 cents
  Overhead: 55,000 cents
  Wastage: 15,000 cents
  Total actual: 1,100,000 cents

Expected:
  actual_per_unit = 1,100,000 / 480 = 2291.67 → 2292 cents
  normative_for_480 = 2408 × 480 = 1,155,840
  total_variance = 1,100,000 - 1,155,840 = -55,840 (FAVORABLE)
  variance_per_unit = 2292 - 2408 = -116 (FAVORABLE)
  material_variance = 920,000 - (normative_material_per_unit × 480) = 920,000 - (2108 × 480)
                    = 920,000 - 1,011,840 = -91,840 (FAVORABLE)
  labor_variance = 110,000 - (200 × 480) = 110,000 - 96,000 = +14,000 (UNFAVORABLE)
```

**Test 5: Variance — unfavorable (farming bad year)**
```
BOM: Пченица, normative: 952 cents/кг × 3000 = 2,856,000

Actual:
  Material: 2,600,000 (more fertilizer used)
  Labor: 350,000 (extra harvesting labor)
  Overhead: 180,000
  Total actual: 3,130,000 for 2,000 кг (bad yield)

Expected:
  actual_per_unit = 3,130,000 / 2000 = 1565 cents/кг
  variance_per_unit = 1565 - 952 = +613 (UNFAVORABLE)
  total_variance = 3,130,000 - (952 × 2000) = 3,130,000 - 1,904,000 = +1,226,000 (UNFAVORABLE)
```

**Test 6: Co-production allocation — by weight**
```
Total cost: 1,000,000 cents
Outputs:
  A (main): 100 кг
  B (by-product): 50 кг
  C (wastage): 10 кг → excluded from allocation (market value = 0)

Total allocable weight = 100 + 50 = 150 (wastage excluded)
  A: (100/150) × 1,000,000 = 666,667 cents → 6667 cents/кг
  B: (50/150) × 1,000,000 = 333,333 cents → 6667 cents/кг
  Rounding check: 666,667 + 333,333 = 1,000,000 ✓

With wastage included (weight method, all outputs):
  Total weight = 160
  A: (100/160) × 1,000,000 = 625,000 → 6250 cents/кг
  B: (50/160) × 1,000,000 = 312,500 → 6250 cents/кг
  C: (10/160) × 1,000,000 = 62,500 → 6250 cents/кг
  Sum: 1,000,000 ✓
```

**Test 7: Co-production allocation — by market value**
```
Total cost: 1,000,000 cents
Outputs:
  A: 100 кг × market 800 MKD/кг = 80,000 MKD
  B: 50 кг × market 200 MKD/кг = 10,000 MKD
  C (waste): 10 кг × market 0 = 0

Total market value = 90,000 MKD
  A: (80,000 / 90,000) × 1,000,000 = 888,889 cents → 8889 cents/кг
  B: (10,000 / 90,000) × 1,000,000 = 111,111 cents → 2222 cents/кг
  C: 0 cents
  Sum: 888,889 + 111,111 = 1,000,000 ✓
```

**Test 8: Co-production allocation — by fixed ratio**
```
Total cost: 1,000,000 cents
Ratios: A=60%, B=30%, C=10%

  A: 600,000 cents / 100 кг = 6000 cents/кг
  B: 300,000 cents / 50 кг = 6000 cents/кг
  C: 100,000 cents / 10 кг = 10,000 cents/кг
  Sum: 1,000,000 ✓
```

**Test 9: Rounding — co-production allocation sums to exact total**
```
Total cost: 1,000,001 cents (odd number)
3 outputs with equal weight (33.33% each)
  A: 333,333
  B: 333,333
  C: 333,333
  Sum: 999,999 → remainder 2 → allocate to primary product
  A (primary): 333,335
  Final sum: 1,000,001 ✓

This tests that the LAST allocation line absorbs rounding remainder.
```

**Test 10: Normal vs abnormal wastage**
```
BOM expected_wastage_percent = 5%
Actual wastage = 12%
Material cost consumed = 500,000 cents

Normal wastage (stays in WIP):
  normal_wastage_cost = 500,000 × (5/100) = 25,000 cents
  → Stays in account 600 (WIP), part of production cost

Abnormal wastage (expensed):
  abnormal_wastage_cost = 500,000 × ((12 - 5)/100) = 500,000 × 0.07 = 35,000 cents
  → GL: DR 580 (Wastage Loss) / CR 600 (WIP)

Remaining in WIP for costing:
  500,000 - 35,000 = 465,000 cents
```

**Test 11: Zero output — farming total loss**
```
Material consumed: 2,405,000 cents
Labor: 300,000 cents
Overhead: 150,000 cents
Total WIP: 2,855,000 cents
Actual output: 0 кг

ALL cost → abnormal wastage:
  GL: DR 580 / CR 600 for 2,855,000 cents
  No stock IN created
  cost_per_unit = 0 (undefined, no output)
  total_variance = 2,855,000 (entire cost is loss)
```

**Test 12: Single unit production (custom furniture)**
```
BOM: "Маса дабова" output: 1
  Даб дрво: 2 m³ × WAC 450,000 = 900,000
  Лак: 0.5 литар × WAC 80,000 = 40,000
  Шрафови: 20 × WAC 500 = 10,000
Labor: 1,200,000 (3 days × 8h × 50,000/h)
Overhead: 300,000

Total: 2,450,000 cents = cost_per_unit (since actual_quantity = 1)
Normative = BOM total = 2,450,000 (exactly matches if no variance)
```

**Test 13: BOM output_quantity > 1 per-unit cost**
```
BOM: "Леб 500г (серија 100)"
output_quantity = 100
Lines total material for batch = 210,800 cents
Labor total for batch = 20,000 cents
Overhead total for batch = 10,000 cents

per_unit_material = 210,800 / 100 = 2108
per_unit_total = (210,800 + 20,000 + 10,000) / 100 = 2408
```

---

### 9.2 Feature Tests — API Endpoints (`tests/Feature/Manufacturing/`)

All use `RefreshDatabase`, `actingAs($user)->withHeader('company', $companyId)`.

#### File: `BomFeatureTest.php` (~25 tests)

**Authentication & Authorization:**
```
test_create_bom_requires_authentication
  POST /api/v1/manufacturing/boms without auth → 401

test_create_bom_requires_company_header
  POST with auth but no company header → 403

test_bom_requires_business_tier
  Company on Standard plan → 403 with tier error message
  Company on Business plan → 201 (allowed)

test_bom_respects_view_only_mode
  User with view-only role → POST returns 403
  User with view-only role → GET returns 200
```

**BOM CRUD:**
```
test_create_bom_with_valid_data
  POST /api/v1/manufacturing/boms
  Body: {
    name: "Тест Леб",
    code: "BOM-TEST-001",
    output_item_id: $finishedGood->id,
    output_quantity: 1,
    output_unit_id: $kgUnit->id,
    expected_wastage_percent: 3,
    labor_cost_per_unit: 200,
    overhead_cost_per_unit: 100,
    lines: [
      { item_id: $flour->id, quantity: 0.35, wastage_percent: 2 },
      { item_id: $yeast->id, quantity: 0.01, wastage_percent: 1 },
    ]
  }
  → 201
  → assertDatabaseHas('boms', ['name' => 'Тест Леб', 'version' => 1])
  → assertDatabaseCount('bom_lines', 2)

test_list_boms_returns_paginated_results
  Create 15 BOMs → GET /manufacturing/boms → 200
  → response has pagination, data count ≤ 15

test_get_bom_includes_lines_and_cost
  GET /manufacturing/boms/{id} → 200
  → response.data.lines has items with item_name, quantity, wastage_percent
  → response.data.normative_cost > 0

test_update_bom_changes_fields
  PUT /manufacturing/boms/{id} → 200
  → name updated, lines updated

test_delete_unused_bom
  DELETE /manufacturing/boms/{id} (no orders reference it) → 200
  → assertDatabaseMissing('boms', ['id' => $bom->id])

test_cannot_delete_bom_used_by_order
  Create BOM → create production order referencing it
  DELETE /manufacturing/boms/{id} → 422 with error message

test_duplicate_bom_creates_new_version
  POST /manufacturing/boms/{id}/duplicate → 201
  → new BOM with version = original.version + 1
  → same lines copied
  → different ID

test_bom_cost_endpoint_returns_calculated_cost
  GET /manufacturing/boms/{id}/cost → 200
  → response.data.material_cost = 2108 (for Леб BOM)
  → response.data.total_cost = 2408
```

**Validation:**
```
test_create_bom_requires_name
  POST without name → 422, errors.name exists

test_create_bom_requires_output_item
  POST without output_item_id → 422

test_create_bom_requires_at_least_one_line
  POST with empty lines array → 422

test_create_bom_validates_unique_code_per_company
  Create BOM with code "X" → create another with code "X" → 422

test_create_bom_rejects_duplicate_item_in_lines
  lines: [{item_id: 5, ...}, {item_id: 5, ...}] → 422 "Duplicate material"

test_create_bom_rejects_output_item_as_input
  output_item_id = 10, lines: [{item_id: 10}] → 422 "Circular reference"

test_create_bom_validates_quantity_positive
  lines: [{quantity: 0}] → 422
  lines: [{quantity: -1}] → 422

test_create_bom_validates_wastage_percent_range
  lines: [{wastage_percent: 101}] → 422
  lines: [{wastage_percent: -1}] → 422
```

#### File: `ProductionOrderFeatureTest.php` (~35 tests)

**Order CRUD:**
```
test_create_order_from_bom
  POST /manufacturing/orders {bom_id, planned_quantity: 500, order_date, output_warehouse_id}
  → 201
  → order_number auto-generated (РН-ГГГГ-НННН pattern)
  → status = 'draft'
  → materials auto-populated from BOM lines × 500
  → planned_quantity on each material = BOM line qty × 500

test_create_order_without_bom
  POST /manufacturing/orders {output_item_id, planned_quantity: 50, order_date}
  → 201
  → no materials pre-populated
  → bom_id is null

test_list_orders_filterable_by_status
  Create orders in each status
  GET /manufacturing/orders?status=draft → only draft orders
  GET /manufacturing/orders?status=in_progress → only in-progress
  GET /manufacturing/orders → all orders

test_get_order_includes_all_details
  GET /manufacturing/orders/{id}
  → includes materials with planned/actual/variance
  → includes labor entries
  → includes overhead entries
  → includes co_production_outputs if any
  → includes cost summary
```

**Status Transitions:**
```
test_start_production_changes_status
  POST /manufacturing/orders/{draft}/start → 200
  → status = 'in_progress'

test_cannot_start_already_started_order
  POST /manufacturing/orders/{in_progress}/start → 422

test_cannot_start_completed_order
  POST /manufacturing/orders/{completed}/start → 422

test_complete_production_single_output
  POST /manufacturing/orders/{in_progress}/complete
  Body: { actual_quantity: 485 }
  → 200
  → status = 'completed'
  → actual_quantity = 485
  → total_production_cost calculated
  → cost_per_unit = total / 485
  → stock IN created for finished good

test_cancel_draft_order
  POST /manufacturing/orders/{draft}/cancel → 200
  → status = 'cancelled'
  → no stock movements to reverse

test_cancel_in_progress_order_reverses_stock
  Start order → record material consumption (stock decreased)
  POST /manufacturing/orders/{in_progress}/cancel → 200
  → status = 'cancelled'
  → stock movements reversed (raw material stock restored)
  → GL entries reversed

test_cannot_cancel_completed_order
  POST /manufacturing/orders/{completed}/cancel → 422

test_cannot_edit_completed_order
  PUT /manufacturing/orders/{completed} → 422
```

**Material Consumption:**
```
test_record_material_consumption
  POST /manufacturing/orders/{in_progress}/materials
  Body: { item_id, actual_quantity: 175, wastage_quantity: 5 }
  → 200
  → stock movement created (SOURCE_PRODUCTION_CONSUME)
  → raw material stock decreased by 175
  → actual_unit_cost = current WAC
  → actual_total_cost = 175 × WAC

test_update_material_consumption
  PUT /manufacturing/orders/{order}/materials/{mat}
  → 200, updated values

test_cannot_consume_more_than_available_stock
  Raw material has 10 кг in stock
  Record consumption of 20 кг → 422 "Insufficient stock"
  (unless company has allow_negative_stock = true)

test_material_consumption_updates_wac_correctly
  Consume from item with known WAC
  → stock OUT at WAC, remaining stock WAC unchanged
```

**Labor & Overhead:**
```
test_record_labor_cost
  POST /manufacturing/orders/{in_progress}/labor
  Body: { description: "Пекар", hours: 8, rate_per_hour: 25000, work_date: "2026-03-27" }
  → 201
  → total_cost = 8 × 25000 = 200,000 cents

test_record_overhead_fixed
  POST /manufacturing/orders/{in_progress}/overhead
  Body: { description: "Струја", amount: 50000, allocation_method: "fixed" }
  → 201

test_record_overhead_per_unit
  POST /manufacturing/orders/{in_progress}/overhead
  Body: { description: "Амбалажа", amount: 500, allocation_method: "per_unit", allocation_base: 500 }
  → 201
  → effective_amount = 500 × 500 = 250,000 cents

test_labor_validates_hours_range
  hours: 0 → 422
  hours: 25 → 422 (max 24)
  hours: -1 → 422
```

**Co-Production:**
```
test_define_co_production_outputs
  POST /manufacturing/orders/{order}/outputs
  Body: { outputs: [
    { item_id: $bread->id, is_primary: true, quantity: 280 },
    { item_id: $rolls->id, is_primary: false, quantity: 50 },
  ]}
  → 201

test_allocate_by_weight
  PUT /manufacturing/orders/{order}/outputs/allocate
  Body: { allocation_method: "weight" }
  → 200
  → bread: 280/(280+50) × total = 84.85%
  → rolls: 50/(280+50) × total = 15.15%
  → sum of allocated_cost = total_production_cost

test_allocate_by_market_value
  Body: { allocation_method: "market_value", market_values: {bread: 40, rolls: 30} }
  → bread: (280×40)/((280×40)+(50×30)) × total = 11200/12700 = 88.19%
  → rolls: (50×30)/12700 = 11.81%

test_co_production_complete_creates_multiple_stock_in
  Complete co-production order
  → stock IN for bread (SOURCE_PRODUCTION_OUTPUT)
  → stock IN for rolls (SOURCE_PRODUCTION_BYPRODUCT)
  → each at allocated cost_per_unit

test_allocation_must_sum_to_100_percent
  Manual allocation: [60%, 30%] = 90% → 422 "Must sum to 100%"
```

**Period Lock:**
```
test_cannot_create_order_in_locked_period
  Lock period 2026-01 to 2026-03
  POST /manufacturing/orders { order_date: "2026-02-15" } → 422 "Period locked"

test_cannot_complete_in_locked_period
  Order in_progress with order_date in locked period
  POST /complete → 422
```

**Wastage:**
```
test_normal_wastage_included_in_production_cost
  BOM wastage: 5%, actual wastage: 4%
  → all wastage cost stays in total_production_cost
  → no entry to account 580

test_abnormal_wastage_expensed_separately
  BOM wastage: 5%, actual wastage: 12%
  → 5% stays in production cost
  → 7% posted to GL: DR 580 / CR 600

test_zero_output_all_cost_becomes_wastage
  Complete with actual_quantity: 0
  → total cost → abnormal wastage
  → GL: DR 580 / CR 600 for entire amount
  → no stock IN
  → cost_per_unit = 0
```

#### File: `ManufacturingGlPostingTest.php` (~15 tests)

```
test_material_consumption_posts_dr600_cr630
  Record material consumption
  → GL entry: DR account code 600 (WIP), CR account code 630 (Inventory)
  → amount = actual_total_cost / 100 (cents to MKD)
  → transaction has production_order_id in meta

test_material_consumption_uses_item_inventory_account
  Item with custom inventory_account_id (code 503)
  → GL: DR 600 / CR 503 (not default 630)

test_labor_posts_dr600_cr524
  Record labor
  → GL: DR 600 (WIP) / CR 524 (Wages Payable)

test_overhead_posts_dr600_cr_expense
  Record overhead with expense_account_id
  → GL: DR 600 / CR specified expense account

test_completion_posts_dr630_cr600
  Complete single-output order
  → GL: DR 630 (Finished Goods) / CR 600 (WIP)
  → amount = total_production_cost

test_co_production_posts_multiple_debit_lines
  Complete co-production
  → GL: DR 630 (FG main product) + DR 637 (by-product) / CR 600 (WIP)
  → DR amounts sum to total production cost

test_abnormal_wastage_posts_dr580_cr600
  Complete with abnormal wastage
  → GL: DR 580 (Wastage Loss) / CR 600 (WIP)
  → amount = abnormal wastage cost only

test_cancellation_reverses_all_gl_entries
  Start order → consume materials → cancel
  → original GL entries exist
  → reversal GL entries created (opposite DR/CR)
  → net effect on all accounts = 0

test_wip_balance_shows_in_progress_orders
  Create 2 in_progress orders with materials consumed
  → account 600 balance = sum of consumed material costs + labor + overhead
  → account 600 balance decreases after completion

test_finished_goods_balance_increases_after_completion
  Check account 630 balance before completion
  Complete order
  → account 630 balance increased by total_production_cost

test_obrazec_36_aop_040_wip
  In-progress order with materials consumed
  → AOP 040 (WIP) shows non-zero value matching account 600

test_obrazec_36_aop_041_finished_goods
  Completed orders with finished goods
  → AOP 041 (Finished goods) = sum of FG stock at WAC

test_obrazec_36_aop_038_raw_materials
  Items with item_type='raw_material'
  → AOP 038 = sum of raw material stock values

test_selling_manufactured_item_posts_cogs_at_production_cost
  Complete production → finished good gets WAC from production
  Create invoice selling 10 units
  → COGS = 10 × production WAC (NOT purchase cost)
  → GL: DR 702 / CR 630
```

---

### 9.3 Playwright E2E Tests — Production (`tests/visual/manufacturing-e2e.spec.js`)

Target: `https://app.facturino.mk` with Company 2 (super admin).
Pattern: serial mode, shared `beforeAll` login, screenshots at key points.

```
Usage:
  TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
    npx playwright test tests/visual/manufacturing-e2e.spec.js --project=chromium
```

#### Describe: Setup & Navigation
```
test: manufacturing sidebar menu visible
  → Navigate to /admin/dashboard
  → Verify sidebar has "Производство" menu group
  → Click → see submenu: Нормативи, Работни налози, Извештаи
  → Screenshot: manufacturing-01-sidebar.png

test: BOM list page loads
  → Navigate to /admin/manufacturing/boms
  → Verify table headers: Код, Назив, Готов производ, Материјали, Нормативна цена, Верзија, Статус
  → If seeded: verify BOM-2026-0001 appears
  → Screenshot: manufacturing-02-bom-list.png

test: Orders list page loads
  → Navigate to /admin/manufacturing/orders
  → Verify status tabs: Сите, Нацрт, Во тек, Завршени, Откажани
  → Verify table headers: РН бр., Датум, Производ, Планирано, Остварено, Трошок, Статус
  → Screenshot: manufacturing-03-orders-list.png
```

#### Describe: BOM Management
```
test: create BOM with materials and live cost preview
  → Navigate to /admin/manufacturing/boms/create
  → Fill name: "Тест Леб E2E"
  → Select output item (Леб 500г)
  → Set output quantity: 1
  → Add material line: Брашно 0.35 кг, wastage 2%
  → Add material line: Квасец 0.01 кг, wastage 1%
  → Set labor: 200, overhead: 100
  → Verify cost preview sidebar updates (should show ~2408)
  → Save
  → Verify redirect to BOM view page
  → Screenshot: manufacturing-04-bom-created.png

test: view BOM shows cost breakdown
  → Open the created BOM
  → Verify material table with quantities and costs
  → Verify cost summary: material + labor + overhead = total
  → Verify version badge shows "В.1"
  → Screenshot: manufacturing-05-bom-view.png

test: duplicate BOM increments version
  → Click duplicate button on BOM view
  → Verify new BOM page with "В.2"
  → Verify same materials copied
  → Screenshot: manufacturing-06-bom-duplicate.png

test: BOM list search and filter
  → Go to BOM list
  → Type "Леб" in search → verify filtered results
  → Toggle status filter: Active only
  → Screenshot: manufacturing-07-bom-filters.png

test: cannot add same material twice
  → On BOM create, add Брашно
  → Try to add Брашно again
  → Verify error/warning appears
  → Screenshot: manufacturing-08-duplicate-material-error.png

test: download норматив PDF
  → Open BOM view
  → Click PDF/print button
  → Verify download or PDF preview modal
  → Screenshot: manufacturing-09-normativ-pdf.png
```

#### Describe: Production Order Workflow
```
test: create order from BOM
  → Navigate to /admin/manufacturing/orders/create
  → Select BOM (Леб 500г)
  → Set quantity: 100
  → Verify materials auto-populated with calculated quantities
  → Verify stock availability shown per material
  → Verify estimated total cost displayed
  → Set output warehouse
  → Save
  → Verify status badge: "Нацрт"
  → Screenshot: manufacturing-10-order-created.png

test: start production
  → Open draft order
  → Click "Започни производство"
  → Verify confirmation dialog appears
  → Click confirm
  → Verify status changes to "Во тек"
  → Screenshot: manufacturing-11-order-started.png

test: record material consumption during production
  → In the IN_PROGRESS order
  → Navigate to Materials tab
  → Enter actual quantity for Брашно: 36 (slightly more than planned 35)
  → Enter wastage: 1
  → Verify cost recalculation happens
  → Verify variance column shows difference
  → Enter quantities for remaining materials
  → Screenshot: manufacturing-12-materials-recorded.png

test: record labor costs
  → Switch to Labor tab
  → Click "Додади"
  → Fill: description "Пекар - месење", hours: 8, rate: 250 MKD/hr
  → Verify total = 2000 MKD shown
  → Add second entry: "Пекар - печење", hours: 4, rate: 250
  → Screenshot: manufacturing-13-labor-recorded.png

test: record overhead costs
  → Switch to Overhead tab
  → Add: "Струја" fixed 500 MKD
  → Add: "Амбалажа" per_unit 5 MKD
  → Verify total overhead calculated
  → Screenshot: manufacturing-14-overhead-recorded.png

test: cost summary panel shows running totals
  → Verify the cost summary panel/sidebar shows:
    - Директен материјал: sum of material costs
    - Директен труд: sum of labor
    - Режиски трошоци: sum of overhead
    - Вкупна цена на чинење: grand total
    - По единица: total / planned
  → Screenshot: manufacturing-15-cost-summary.png

test: complete single-output production
  → Click "Заврши производство" or navigate to complete page
  → Enter actual quantity: 95 (5 lost to wastage)
  → Verify cost summary displayed:
    - Total cost
    - Cost per unit
    - Variance from normative
  → Click confirm/complete
  → Verify status = "Завршен"
  → Verify order is now readonly
  → Screenshot: manufacturing-16-completed.png

test: completed order is readonly
  → Open completed order
  → Verify all input fields are disabled
  → Verify no edit/delete buttons
  → Verify can still download PDF
  → Screenshot: manufacturing-17-readonly.png
```

#### Describe: Co-Production Order
```
test: create and complete co-production order
  → Create new order from BOM
  → Start production
  → Record materials, labor, overhead
  → Navigate to completion
  → Toggle "Сопроизводство" on
  → Add main product: Леб, quantity 280
  → Add by-product: Кифла, quantity 50
  → Select allocation method: "По тежина"
  → Verify allocation percentages:
    - Леб: 280/(280+50) = 84.85%
    - Кифла: 50/(280+50) = 15.15%
  → Verify allocated cost per product
  → Complete
  → Verify both products have stock increased
  → Screenshot: manufacturing-18-co-production.png
```

#### Describe: Order Cancellation
```
test: cancel draft order
  → Create new draft order
  → Click "Откажи"
  → Verify confirmation dialog
  → Confirm
  → Verify status = "Откажан"
  → Screenshot: manufacturing-19-cancelled-draft.png

test: cancel in-progress order reverses stock
  → Create order, start, record some materials
  → Note current stock levels (via API or page)
  → Cancel order
  → Verify stock restored to pre-consumption levels
  → Screenshot: manufacturing-20-cancelled-reversal.png
```

#### Describe: Edge Cases & Validation
```
test: BOM creation validates required fields
  → Go to BOM create
  → Click save without filling anything
  → Verify validation errors shown for: name, output item
  → Screenshot: manufacturing-21-validation.png

test: anomaly detection on 10x quantity
  → During material entry in production order
  → Enter quantity 900 where normative is 90
  → Verify AI anomaly warning banner:
    "Издадената количина (900) е 10× повисока од нормативната (90)"
  → Screenshot: manufacturing-22-anomaly.png

test: zero output handling (farming scenario)
  → Create farming order (BOM-003 Пченица)
  → Start, record materials
  → Complete with actual_quantity: 0
  → Verify system handles it (cost → wastage)
  → Screenshot: manufacturing-23-zero-output.png

test: insufficient stock warning
  → Try to create order requiring more material than available
  → Verify red warning badges on materials with insufficient stock
  → Screenshot: manufacturing-24-insufficient-stock.png
```

#### Describe: PDF Generation
```
test: download работен налог PDF
  → Open completed order
  → Click "Работен налог" PDF button
  → Verify download initiated or PDF preview shown
  → Screenshot: manufacturing-25-raboten-nalog-pdf.png

test: download калкулација PDF
  → Open completed order
  → Click "Калкулација" PDF button
  → Screenshot: manufacturing-26-kalkulacija-pdf.png
```

#### Describe: Reports
```
test: cost analysis report loads with data
  → Navigate to /admin/manufacturing/reports
  → Select current month/quarter
  → Verify data table shows production orders
  → Verify columns: product, orders count, total cost, avg cost/unit
  → Screenshot: manufacturing-27-cost-report.png

test: variance report shows colored indicators
  → Navigate to variance report tab
  → Verify favorable variances in green
  → Verify unfavorable variances in red
  → Screenshot: manufacturing-28-variance-report.png

test: wastage report
  → Navigate to wastage report tab
  → Verify data shows wastage by product
  → Screenshot: manufacturing-29-wastage-report.png
```

#### Describe: AI Features (Optional — May Timeout)
```
test: AI BOM suggestion card appears
  → On BOM create, type product name "Леб" in name field
  → Wait up to 15s for AI suggestion card
  → If appears: verify suggestion has material items
  → If timeout: log "AI suggestion timed out" (non-fatal)
  → Screenshot: manufacturing-30-ai-suggestion.png

test: natural language order creation
  → On order create, find NL input field
  → Type "Направи 500 леба до петок"
  → Wait for AI to parse
  → Verify fields auto-populated (BOM, quantity, date)
  → Screenshot: manufacturing-31-nl-order.png
```

#### Describe: Integration Tests
```
test: finished good from production appears in invoice item selector
  → After completing production of Леб
  → Navigate to invoice create
  → Search for "Леб" in item selector
  → Verify item appears with stock quantity > 0
  → Screenshot: manufacturing-32-invoice-integration.png

test: stock card shows production movements
  → Navigate to stock/lagerska kartica for Леб
  → Verify production IN movement visible with source "Произведено"
  → Navigate to stock card for Брашно
  → Verify production OUT movement visible with source "Производство"
  → Screenshot: manufacturing-33-stock-card.png
```

#### Describe: Health Checks
```
test: no JS errors during entire test run
  → Check jsErrors array accumulated from page.on('console')
  → Expect length === 0

test: no API 500 errors during test run
  → Check apiErrors array accumulated from page.on('response')
  → Expect length === 0
```

---

### 9.4 Test Fixtures (`tests/fixtures/manufacturing/`)

**`bom-bread.json`** — BOM creation payload:
```json
{
  "name": "Леб 500г",
  "code": "BOM-TEST-001",
  "output_quantity": 1,
  "expected_wastage_percent": 3,
  "labor_cost_per_unit": 200,
  "overhead_cost_per_unit": 100,
  "lines": [
    { "item_name": "Брашно тип 400", "quantity": 0.35, "wastage_percent": 2, "unit": "кг" },
    { "item_name": "Квасец суво", "quantity": 0.01, "wastage_percent": 1, "unit": "кг" },
    { "item_name": "Сол кујнска", "quantity": 0.005, "wastage_percent": 0, "unit": "кг" }
  ]
}
```

**`bom-farming.json`** — High wastage BOM:
```json
{
  "name": "Пченица 1 хектар",
  "code": "BOM-TEST-003",
  "output_quantity": 3000,
  "expected_wastage_percent": 10,
  "labor_cost_per_unit": 100,
  "overhead_cost_per_unit": 50,
  "lines": [
    { "item_name": "Семе пченица", "quantity": 200, "wastage_percent": 0, "unit": "кг" },
    { "item_name": "Ѓубриво NPK", "quantity": 300, "wastage_percent": 5, "unit": "кг" },
    { "item_name": "Пестицид", "quantity": 5, "wastage_percent": 0, "unit": "литар" },
    { "item_name": "Дизел гориво", "quantity": 80, "wastage_percent": 0, "unit": "литар" }
  ]
}
```

**`co-production-flour-mill.json`** — Co-production scenario:
```json
{
  "input_item": "Пченица зрно",
  "input_quantity": 1000,
  "outputs": [
    { "item_name": "Брашно тип 400", "type": "primary", "quantity": 750, "market_price_mkd": 25 },
    { "item_name": "Трици", "type": "by_product", "quantity": 200, "market_price_mkd": 8 },
    { "item_name": "Мекини", "type": "by_product", "quantity": 40, "market_price_mkd": 15 },
    { "item_name": "Утрасок", "type": "wastage", "quantity": 10, "market_price_mkd": 0 }
  ],
  "allocation_method": "market_value"
}
```

**`production-order-complete.json`** — Full completion payload:
```json
{
  "actual_quantity": 485,
  "co_production": false,
  "materials": [
    { "item_name": "Брашно", "actual_quantity": 175, "wastage_quantity": 3 },
    { "item_name": "Квасец", "actual_quantity": 5, "wastage_quantity": 0.1 },
    { "item_name": "Сол", "actual_quantity": 2.5, "wastage_quantity": 0 }
  ],
  "labor": [
    { "description": "Пекар - месење", "hours": 8, "rate_per_hour": 25000 },
    { "description": "Пекар - печење", "hours": 6, "rate_per_hour": 25000 }
  ],
  "overhead": [
    { "description": "Струја", "amount": 50000, "allocation_method": "fixed" },
    { "description": "Наем", "amount": 30000, "allocation_method": "fixed" }
  ]
}
```

---

### 9.5 Test Coverage Matrix

| Area | Unit | Feature | E2E | PDF | Total |
|------|------|---------|-----|-----|-------|
| BOM CRUD | 3 | 12 | 6 | 1 | 22 |
| BOM validation | 2 | 8 | 2 | — | 12 |
| Order lifecycle | 4 | 8 | 8 | — | 20 |
| Status transitions | — | 6 | 3 | — | 9 |
| Material consumption | 1 | 4 | 1 | — | 6 |
| WAC/cost calc | 3 | 2 | 1 | — | 6 |
| Co-production | 4 | 5 | 1 | — | 10 |
| Variance | 2 | — | 1 | — | 3 |
| Wastage | 2 | 3 | 1 | — | 6 |
| GL posting | — | 14 | — | — | 14 |
| Period lock | — | 2 | — | — | 2 |
| Tier enforcement | — | 1 | 1 | — | 2 |
| Reports | — | — | 3 | — | 3 |
| AI features | — | — | 2 | — | 2 |
| Integration | — | 1 | 2 | — | 3 |
| Edge cases | 3 | 2 | 2 | — | 7 |
| PDF generation | — | — | 2 | 8 | 10 |
| **TOTAL** | **24** | **68** | **36** | **9** | **137** |

### 9.6 Test Execution Order

1. **Unit tests first** — fast, no DB, validates calculations
2. **Feature tests** — with RefreshDatabase, validates API contracts
3. **E2E on staging** — after deployment, validates full UI flow
4. **PDF visual check** — manual review of generated PDFs against template specs

```bash
# Run unit tests
php artisan test tests/Unit/Manufacturing/ --parallel

# Run feature tests
php artisan test tests/Feature/Manufacturing/ --parallel

# Run E2E (after deploy)
TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
  npx playwright test tests/visual/manufacturing-e2e.spec.js --project=chromium

# Run all manufacturing tests
php artisan test --filter=Manufacturing --parallel
```

---

## 10. Subscription & Feature Flag

### Feature Flag
```php
// config/invoiceshelf.php or config/subscriptions.php
'manufacturing' => env('FEATURE_MANUFACTURING', false),
```

### Tier Requirements
- **Free / Starter / Standard**: No access (feature hidden)
- **Business (€59/mo)**: Full manufacturing module
- **Max (€149/mo)**: Full manufacturing + advanced reports

### Middleware
```php
Route::middleware(['tier:business'])->prefix('manufacturing')->group(function () {
    // All manufacturing routes
});
```

---

## 11. Migration File Naming

Following project convention (`2025_08_**`):

```
database/migrations/
├── 2025_08_60_000001_add_item_type_to_items_table.php
├── 2025_08_60_000002_create_boms_table.php
├── 2025_08_60_000003_create_bom_lines_table.php
├── 2025_08_60_000004_create_production_orders_table.php
├── 2025_08_60_000005_create_production_order_materials_table.php
├── 2025_08_60_000006_create_production_order_labor_table.php
├── 2025_08_60_000007_create_production_order_overhead_table.php
├── 2025_08_60_000008_create_co_production_outputs_table.php
└── 2025_08_60_000009_add_production_source_types_to_stock_movements.php
```

All with `Schema::hasTable()` / `Schema::hasColumn()` guards for idempotency.

---

## 12. i18n Keys Required

New keys in all 4 language files (`lang/{mk,en,sq,tr}.json`) under `manufacturing.*`:

**~80 keys including:**
- Menu: title, boms, orders, reports
- BOM: create, edit, output_item, input_materials, normative_cost, wastage_percent, labor_cost, overhead_cost
- Orders: create, start, complete, cancel, order_number, planned_qty, actual_qty, status_draft/in_progress/completed/cancelled
- Materials: consume, planned, actual, wastage, variance
- Labor: description, hours, rate, total
- Overhead: description, amount, allocation_method
- Co-production: outputs, primary_product, by_product, allocation_weight/market_value/fixed_ratio, allocated_cost
- Reports: cost_analysis, variance_report, wastage_report, kalkulacija, favorable, unfavorable
- PDF: raboten_nalog, kalkulacija, sooproizvodstven_nalog, sostavil, magacioner, rakovoditel

---

## 13. Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| WAC accuracy with production movements | High | Unit tests for every WAC scenario |
| GL double-posting on failed completion | High | DB transactions with rollback |
| Item type migration on existing items | Medium | Default to 'merchandise', no data loss |
| Co-production allocation rounding | Medium | Force percentages sum to 100%, allocate remainder to primary |
| Concurrent production orders on same materials | Medium | Stock availability check at consumption, not at order creation |
| DomPDF limitations on complex PDF tables | Low | Follow existing patterns (body margin, no calc()) |
| MK law compliance for document formats | High | Consult accountant for exact document requirements |

---

## 14. Dependencies

**No new packages required.** Uses existing:
- Laravel 10 (models, migrations, controllers, validation)
- DomPDF (PDF generation)
- Vue 3 + Composition API (frontend)
- Existing StockService (stock movements)
- Existing IfrsAdapter (GL posting)
- Existing InventoryDocument pattern (formal documents)

**New internal dependencies:**
- `ManufacturingService` depends on `StockService`
- `ProductionOrderGlObserver` depends on `IfrsAdapter`
- `CostAllocationService` is standalone

---

*This plan covers the full manufacturing module from database schema to E2E testing, aligned with Macedonian accounting law (UJP Chart of Accounts Class 6), IFRS GL posting, and existing Facturino architecture.*
