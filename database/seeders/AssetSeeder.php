<?php

namespace Database\Seeders;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\Currency;
use App\Enums\Role;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    /**
     * Seed a realistic small-office asset estate (~40 items).
     */
    public function run(): void
    {
        $categories = Category::query()->pluck('id', 'name');
        $locations = Location::query()->pluck('id', 'name');
        $adminId = User::query()->where('role', Role::Admin->value)->value('id');

        foreach ($this->assets() as $row) {
            Asset::create([
                'name' => $row['name'],
                'category_id' => $categories[$row['category']] ?? null,
                'location_id' => isset($row['location']) ? ($locations[$row['location']] ?? null) : null,
                'status' => AssetStatus::Available,
                'condition' => AssetCondition::from($row['condition']),
                'serial_number' => $row['serial'] ?? null,
                'manufacturer' => $row['manufacturer'] ?? null,
                'model' => $row['model'] ?? null,
                'purchase_date' => $row['purchase'] ?? null,
                'purchase_cost' => $row['cost'] ?? null,
                'currency' => Currency::from($row['currency'] ?? 'MYR'),
                'salvage_value' => $row['salvage'] ?? null,
                'useful_life_years' => $row['life'] ?? null,
                'warranty_expiry' => $row['warranty'] ?? null,
                'supplier' => $row['supplier'] ?? null,
                'created_by' => $adminId,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assets(): array
    {
        return [
            // Laptops
            ['name' => 'Dell Latitude 5440 Business Laptop', 'category' => 'Laptops', 'location' => 'Open Office', 'manufacturer' => 'Dell', 'model' => 'Latitude 5440', 'serial' => 'DL-LAT-5440-001', 'purchase' => '2023-03-15', 'cost' => 5200, 'salvage' => 520, 'life' => 5, 'warranty' => '2026-03-15', 'supplier' => 'Dell Malaysia', 'condition' => 'good'],
            ['name' => 'HP ProBook 450 G9 Laptop', 'category' => 'Laptops', 'location' => 'Open Office', 'manufacturer' => 'HP', 'model' => 'ProBook 450 G9', 'serial' => 'HP-PB450-002', 'purchase' => '2022-09-01', 'cost' => 4100, 'salvage' => 410, 'life' => 5, 'warranty' => '2025-09-01', 'supplier' => 'HP Malaysia', 'condition' => 'good'],
            ['name' => 'Lenovo ThinkPad T14 Gen 3', 'category' => 'Laptops', 'location' => 'Open Office', 'manufacturer' => 'Lenovo', 'model' => 'ThinkPad T14 Gen 3', 'serial' => 'LN-T14G3-003', 'purchase' => '2023-06-20', 'cost' => 5600, 'salvage' => 560, 'life' => 5, 'warranty' => '2026-06-20', 'supplier' => 'Lenovo Malaysia', 'condition' => 'new'],
            ['name' => 'Apple MacBook Air M2 13"', 'category' => 'Laptops', 'location' => 'Management Office', 'manufacturer' => 'Apple', 'model' => 'MacBook Air M2', 'serial' => 'AP-MBA-M2-004', 'purchase' => '2022-11-10', 'cost' => 4999, 'salvage' => 900, 'life' => 5, 'warranty' => '2025-11-10', 'supplier' => 'Machines', 'condition' => 'good'],
            ['name' => 'Acer Aspire 5 (Front Desk)', 'category' => 'Laptops', 'location' => 'Reception', 'manufacturer' => 'Acer', 'model' => 'Aspire 5', 'serial' => 'AC-A5-005', 'purchase' => '2021-05-05', 'cost' => 2999, 'salvage' => 300, 'life' => 4, 'warranty' => '2024-05-05', 'supplier' => 'Acer Malaysia', 'condition' => 'fair'],

            // Desktops & Monitors
            ['name' => 'Dell OptiPlex 7010 Desktop', 'category' => 'Desktops & Monitors', 'location' => 'Open Office', 'manufacturer' => 'Dell', 'model' => 'OptiPlex 7010', 'serial' => 'DL-OPT-7010-006', 'purchase' => '2022-02-18', 'cost' => 3800, 'salvage' => 380, 'life' => 5, 'warranty' => '2025-02-18', 'supplier' => 'Dell Malaysia', 'condition' => 'good'],
            ['name' => 'Dell UltraSharp U2723QE 27" Monitor', 'category' => 'Desktops & Monitors', 'location' => 'Open Office', 'manufacturer' => 'Dell', 'model' => 'U2723QE', 'serial' => 'DL-U2723-007', 'purchase' => '2023-01-10', 'cost' => 2200, 'life' => 6, 'warranty' => '2026-01-10', 'supplier' => 'Dell Malaysia', 'condition' => 'good'],
            ['name' => 'Samsung 24" Essential Monitor', 'category' => 'Desktops & Monitors', 'location' => 'Reception', 'manufacturer' => 'Samsung', 'model' => 'LS24C310', 'serial' => 'SM-24C310-008', 'purchase' => '2021-08-22', 'cost' => 650, 'life' => 5, 'warranty' => '2024-08-22', 'supplier' => 'Samsung Malaysia', 'condition' => 'good'],
            ['name' => 'ASUS VA24E 24" Monitor', 'category' => 'Desktops & Monitors', 'location' => 'Open Office', 'manufacturer' => 'ASUS', 'model' => 'VA24E', 'serial' => 'AS-VA24E-009', 'purchase' => '2022-12-01', 'cost' => 599, 'life' => 5, 'warranty' => '2025-12-01', 'supplier' => 'All IT', 'condition' => 'good'],
            ['name' => 'LG 27" 4K Monitor (Design)', 'category' => 'Desktops & Monitors', 'location' => 'Open Office', 'manufacturer' => 'LG', 'model' => '27UP850', 'serial' => 'LG-27UP850-010', 'purchase' => '2023-04-15', 'cost' => 2399, 'life' => 6, 'warranty' => '2026-04-15', 'supplier' => 'LG Malaysia', 'condition' => 'new'],

            // Peripherals
            ['name' => 'Logitech MX Master 3S Mouse', 'category' => 'Peripherals', 'location' => 'Open Office', 'manufacturer' => 'Logitech', 'model' => 'MX Master 3S', 'serial' => 'LO-MXM3S-011', 'purchase' => '2023-02-01', 'cost' => 450, 'life' => 3, 'warranty' => '2025-02-01', 'supplier' => 'Logitech', 'condition' => 'good'],
            ['name' => 'Logitech K380 Keyboard', 'category' => 'Peripherals', 'location' => 'Open Office', 'manufacturer' => 'Logitech', 'model' => 'K380', 'serial' => 'LO-K380-012', 'purchase' => '2023-02-01', 'cost' => 180, 'life' => 3, 'warranty' => '2025-02-01', 'supplier' => 'Logitech', 'condition' => 'good'],
            ['name' => 'Anker USB-C Docking Station', 'category' => 'Peripherals', 'location' => 'Open Office', 'manufacturer' => 'Anker', 'model' => 'PowerExpand 555', 'serial' => 'AN-PE555-013', 'purchase' => '2023-01-20', 'cost' => 780, 'life' => 3, 'warranty' => '2025-01-20', 'supplier' => 'Anker', 'condition' => 'good'],
            ['name' => 'Jabra Evolve2 40 Headset', 'category' => 'Peripherals', 'location' => 'Open Office', 'manufacturer' => 'Jabra', 'model' => 'Evolve2 40', 'serial' => 'JA-EV240-014', 'purchase' => '2022-10-05', 'cost' => 690, 'life' => 3, 'warranty' => '2024-10-05', 'supplier' => 'Jabra', 'condition' => 'good'],
            ['name' => 'Logitech C920 HD Webcam', 'category' => 'Peripherals', 'location' => 'Open Office', 'manufacturer' => 'Logitech', 'model' => 'C920', 'serial' => 'LO-C920-015', 'purchase' => '2023-03-01', 'cost' => 320, 'life' => 3, 'warranty' => '2025-03-01', 'supplier' => 'Logitech', 'condition' => 'good'],

            // Printing & Scanning
            ['name' => 'HP LaserJet Pro M404dn Printer', 'category' => 'Printing & Scanning', 'location' => 'Open Office', 'manufacturer' => 'HP', 'model' => 'LaserJet Pro M404dn', 'serial' => 'HP-M404-016', 'purchase' => '2021-07-12', 'cost' => 1899, 'life' => 5, 'warranty' => '2024-07-12', 'supplier' => 'HP Malaysia', 'condition' => 'fair'],
            ['name' => 'Canon imageRUNNER C3226i Copier', 'category' => 'Printing & Scanning', 'location' => 'Open Office', 'manufacturer' => 'Canon', 'model' => 'imageRUNNER C3226i', 'serial' => 'CN-C3226-017', 'purchase' => '2022-04-04', 'cost' => 12800, 'salvage' => 2000, 'life' => 7, 'warranty' => '2027-04-04', 'supplier' => 'Canon Malaysia', 'condition' => 'good'],
            ['name' => 'Epson WorkForce DS-530 Scanner', 'category' => 'Printing & Scanning', 'location' => 'Open Office', 'manufacturer' => 'Epson', 'model' => 'WorkForce DS-530', 'serial' => 'EP-DS530-018', 'purchase' => '2021-09-30', 'cost' => 1450, 'life' => 5, 'warranty' => '2024-09-30', 'supplier' => 'Epson Malaysia', 'condition' => 'good'],

            // Networking
            ['name' => 'Ubiquiti UniFi 6 Pro Access Point', 'category' => 'Networking', 'location' => 'Open Office', 'manufacturer' => 'Ubiquiti', 'model' => 'U6-Pro', 'serial' => 'UB-U6PRO-019', 'purchase' => '2022-06-15', 'cost' => 899, 'life' => 5, 'warranty' => '2025-06-15', 'supplier' => 'Netsec', 'condition' => 'good'],
            ['name' => 'TP-Link Omada ER605 Router', 'category' => 'Networking', 'location' => 'Server Room', 'manufacturer' => 'TP-Link', 'model' => 'ER605', 'serial' => 'TP-ER605-020', 'purchase' => '2022-06-15', 'cost' => 420, 'life' => 5, 'warranty' => '2025-06-15', 'supplier' => 'Netsec', 'condition' => 'good'],
            ['name' => 'Netgear GS308E 8-Port Switch', 'category' => 'Networking', 'location' => 'Server Room', 'manufacturer' => 'Netgear', 'model' => 'GS308E', 'serial' => 'NG-GS308-021', 'purchase' => '2021-11-11', 'cost' => 260, 'life' => 5, 'warranty' => '2024-11-11', 'supplier' => 'Netsec', 'condition' => 'good'],
            ['name' => 'D-Link DGS-1024 Switch', 'category' => 'Networking', 'location' => 'Server Room', 'manufacturer' => 'D-Link', 'model' => 'DGS-1024', 'serial' => 'DL-DGS1024-022', 'purchase' => '2020-10-01', 'cost' => 699, 'life' => 5, 'warranty' => '2023-10-01', 'supplier' => 'Netsec', 'condition' => 'fair'],

            // Servers & Storage
            ['name' => 'Synology DS923+ NAS', 'category' => 'Servers & Storage', 'location' => 'Server Room', 'manufacturer' => 'Synology', 'model' => 'DS923+', 'serial' => 'SY-DS923-023', 'purchase' => '2023-05-05', 'cost' => 6800, 'salvage' => 1200, 'life' => 5, 'warranty' => '2026-05-05', 'supplier' => 'Synology', 'condition' => 'new'],
            ['name' => 'Dell PowerEdge T150 Server', 'category' => 'Servers & Storage', 'location' => 'Server Room', 'manufacturer' => 'Dell', 'model' => 'PowerEdge T150', 'serial' => 'DL-PET150-024', 'purchase' => '2021-03-20', 'cost' => 14500, 'salvage' => 2500, 'life' => 6, 'warranty' => '2025-03-20', 'supplier' => 'Dell Malaysia', 'condition' => 'good'],
            ['name' => 'APC Smart-UPS 1500VA', 'category' => 'Servers & Storage', 'location' => 'Server Room', 'manufacturer' => 'APC', 'model' => 'SMT1500', 'serial' => 'AP-SMT1500-025', 'purchase' => '2021-03-20', 'cost' => 3200, 'life' => 5, 'warranty' => '2024-03-20', 'supplier' => 'APC Malaysia', 'condition' => 'good'],
            ['name' => 'Seagate 8TB Enterprise HDD (Spare)', 'category' => 'Servers & Storage', 'location' => 'Store Room', 'manufacturer' => 'Seagate', 'model' => 'ST8000NM', 'serial' => 'SG-ST8000-026', 'purchase' => '2023-05-05', 'cost' => 1200, 'life' => 5, 'warranty' => '2026-05-05', 'supplier' => 'Synology', 'condition' => 'new'],

            // Smartphones
            ['name' => 'Apple iPhone 15 128GB', 'category' => 'Smartphones', 'location' => 'Management Office', 'manufacturer' => 'Apple', 'model' => 'iPhone 15', 'serial' => 'AP-IP15-027', 'purchase' => '2023-10-01', 'cost' => 4399, 'life' => 4, 'warranty' => '2025-10-01', 'supplier' => 'Machines', 'condition' => 'good'],
            ['name' => 'Samsung Galaxy S24', 'category' => 'Smartphones', 'location' => 'Management Office', 'manufacturer' => 'Samsung', 'model' => 'Galaxy S24', 'serial' => 'SM-GS24-028', 'purchase' => '2024-02-14', 'cost' => 4199, 'life' => 4, 'warranty' => '2026-02-14', 'supplier' => 'Samsung Malaysia', 'condition' => 'new'],
            ['name' => 'Xiaomi Redmi Note 13 (Backup)', 'category' => 'Smartphones', 'location' => 'Store Room', 'manufacturer' => 'Xiaomi', 'model' => 'Redmi Note 13', 'serial' => 'XI-RN13-029', 'purchase' => '2024-01-20', 'cost' => 1099, 'life' => 3, 'warranty' => '2025-01-20', 'supplier' => 'Xiaomi', 'condition' => 'new'],

            // Tablets
            ['name' => 'Apple iPad 10.9" (Sales)', 'category' => 'Tablets', 'location' => 'Open Office', 'manufacturer' => 'Apple', 'model' => 'iPad 10.9', 'serial' => 'AP-IPAD10-030', 'purchase' => '2023-07-07', 'cost' => 1999, 'life' => 4, 'warranty' => '2025-07-07', 'supplier' => 'Machines', 'condition' => 'good'],

            // Desks
            ['name' => 'IKEA BEKANT Sit/Stand Desk', 'category' => 'Desks', 'location' => 'Open Office', 'manufacturer' => 'IKEA', 'model' => 'BEKANT', 'serial' => 'IK-BEKANT-031', 'purchase' => '2021-02-02', 'cost' => 1790, 'life' => 10, 'warranty' => '2026-02-02', 'supplier' => 'IKEA', 'condition' => 'good'],
            ['name' => 'IKEA LINNMON Meeting Table', 'category' => 'Desks', 'location' => 'Meeting Room', 'manufacturer' => 'IKEA', 'model' => 'LINNMON', 'serial' => 'IK-LINN-032', 'purchase' => '2020-06-15', 'cost' => 450, 'life' => 8, 'supplier' => 'IKEA', 'condition' => 'fair'],

            // Chairs
            ['name' => 'Herman Miller Aeron Chair', 'category' => 'Chairs', 'location' => 'Management Office', 'manufacturer' => 'Herman Miller', 'model' => 'Aeron', 'serial' => 'HM-AERON-033', 'purchase' => '2021-02-02', 'cost' => 6800, 'life' => 12, 'warranty' => '2026-02-02', 'supplier' => 'Xtra Furniture', 'condition' => 'good'],
            ['name' => 'IKEA MARKUS Office Chair', 'category' => 'Chairs', 'location' => 'Open Office', 'manufacturer' => 'IKEA', 'model' => 'MARKUS', 'serial' => 'IK-MARKUS-034', 'purchase' => '2022-03-03', 'cost' => 699, 'life' => 7, 'supplier' => 'IKEA', 'condition' => 'good'],

            // Cabinets
            ['name' => 'Steelcase 4-Drawer Filing Cabinet', 'category' => 'Cabinets', 'location' => 'Open Office', 'manufacturer' => 'Steelcase', 'model' => '4-Drawer', 'serial' => 'SC-4DR-035', 'purchase' => '2020-05-01', 'cost' => 850, 'life' => 10, 'supplier' => 'Steelcase', 'condition' => 'good'],

            // Meeting Room
            ['name' => 'Epson EB-FH06 Projector', 'category' => 'Meeting Room', 'location' => 'Meeting Room', 'manufacturer' => 'Epson', 'model' => 'EB-FH06', 'serial' => 'EP-EBFH06-036', 'purchase' => '2022-08-08', 'cost' => 3299, 'life' => 6, 'warranty' => '2025-08-08', 'supplier' => 'Epson Malaysia', 'condition' => 'good'],
            ['name' => 'Poly Studio USB Video Bar', 'category' => 'Meeting Room', 'location' => 'Meeting Room', 'manufacturer' => 'Poly', 'model' => 'Studio', 'serial' => 'PO-STUDIO-037', 'purchase' => '2023-03-15', 'cost' => 4500, 'life' => 5, 'warranty' => '2026-03-15', 'supplier' => 'Poly', 'condition' => 'good'],

            // Pantry
            ['name' => 'Panasonic Microwave Oven', 'category' => 'Pantry', 'location' => 'Pantry', 'manufacturer' => 'Panasonic', 'model' => 'NN-GT35', 'serial' => 'PA-NNGT35-038', 'purchase' => '2021-04-10', 'cost' => 499, 'life' => 6, 'warranty' => '2024-04-10', 'supplier' => 'Harvey Norman', 'condition' => 'good'],
            ['name' => 'Bosch Coffee Machine', 'category' => 'Pantry', 'location' => 'Pantry', 'manufacturer' => 'Bosch', 'model' => 'TKA6A043', 'serial' => 'BO-TKA6-039', 'purchase' => '2022-05-05', 'cost' => 899, 'life' => 5, 'warranty' => '2025-05-05', 'supplier' => 'Harvey Norman', 'condition' => 'good'],
            ['name' => 'Sharp 2-Door Refrigerator', 'category' => 'Pantry', 'location' => 'Pantry', 'manufacturer' => 'Sharp', 'model' => 'SJ-XP', 'serial' => 'SH-SJXP-040', 'purchase' => '2020-03-01', 'cost' => 1599, 'life' => 8, 'supplier' => 'Harvey Norman', 'condition' => 'fair'],

            // Air Conditioning
            ['name' => 'Daikin 1.5HP Inverter Aircond (Open Office)', 'category' => 'Air Conditioning', 'location' => 'Open Office', 'manufacturer' => 'Daikin', 'model' => 'FTKF35', 'serial' => 'DK-FTKF35-041', 'purchase' => '2021-01-15', 'cost' => 2200, 'life' => 8, 'warranty' => '2024-01-15', 'supplier' => 'Daikin Malaysia', 'condition' => 'good'],
            ['name' => 'Daikin 1.5HP Inverter Aircond (Meeting Room)', 'category' => 'Air Conditioning', 'location' => 'Meeting Room', 'manufacturer' => 'Daikin', 'model' => 'FTKF35', 'serial' => 'DK-FTKF35-042', 'purchase' => '2021-01-15', 'cost' => 2200, 'life' => 8, 'warranty' => '2024-01-15', 'supplier' => 'Daikin Malaysia', 'condition' => 'good'],

            // Software Licenses
            ['name' => 'Microsoft 365 Business Standard (10 seats)', 'category' => 'Software Licenses', 'manufacturer' => 'Microsoft', 'model' => '365 Business Standard', 'purchase' => '2024-01-01', 'cost' => 3300, 'life' => 1, 'warranty' => '2025-01-01', 'supplier' => 'Microsoft', 'condition' => 'good'],
            ['name' => 'Adobe Creative Cloud (Design, 2 seats)', 'category' => 'Software Licenses', 'manufacturer' => 'Adobe', 'model' => 'Creative Cloud', 'purchase' => '2024-03-01', 'cost' => 660, 'currency' => 'USD', 'life' => 1, 'warranty' => '2025-03-01', 'supplier' => 'Adobe', 'condition' => 'good'],
            ['name' => 'Autodesk AutoCAD LT (1 seat)', 'category' => 'Software Licenses', 'manufacturer' => 'Autodesk', 'model' => 'AutoCAD LT', 'purchase' => '2023-09-01', 'cost' => 490, 'currency' => 'USD', 'life' => 1, 'warranty' => '2024-09-01', 'supplier' => 'Autodesk', 'condition' => 'good'],
        ];
    }
}
