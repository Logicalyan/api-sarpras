<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin Sarpras',
                'email' => 'admin@sarpras.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'profile' => [
                    'phone' => '081234567890',
                    'address' => 'Kantor Sarpras'
                ]
            ],
            [
                'name' => 'Peminjam',
                'email' => 'borrower@sarpras.com',
                'password' => Hash::make('borrower123'),
                'role' => 'borrower',
                'profile' => [
                    'phone' => '082233445566',
                    'address' => 'Lab Komputer'
                ]
            ]
        ];

        foreach ($users as $item) {
            $user = User::create([
                'name' => $item['name'],
                'email' => $item['email'],
                'password' => $item['password'],
                'role' => $item['role']
            ]);

            $user->profile()->create($item['profile']);
        }

        DB::transaction(function () {
            // Warehouses
            $warehouse = Warehouse::firstOrCreate([
                'name' => 'Gudang Utama',
                'location' => 'Lantai 1'
            ]);

            // Categories
            $alatTulis = Category::firstOrCreate(['name' => 'Alat Tulis', 'description' => 'Alat tulis kantor']);
            $elektronik = Category::firstOrCreate(['name' => 'Elektronik', 'description' => 'Elektronik kantor']);

            // Items
            $pensil = Item::create([
                'name' => 'Pensil',
                'code' => 'PEN',
                'type' => 'consumable',
                'category_id' => $alatTulis->id,
                'warehouse_id' => $warehouse->id,
                'stock' => 100,
            ]);

            $proyektor = Item::create([
                'name' => 'Proyektor Epson',
                'code' => 'PRY',
                'type' => 'reusable',
                'category_id' => $elektronik->id,
                'warehouse_id' => $warehouse->id,
                'stock' => 3,
            ]);

            // Stock transactions for consumable
            StockTransaction::create([
                'item_id' => $pensil->id,
                'type' => 'in',
                'quantity' => 100,
                'description' => 'Stok awal pensil'
            ]);

            // Stock transactions for reusable (each with unit)
            $qtyProyektor = 3;
            StockTransaction::create([
                'item_id' => $proyektor->id,
                'type' => 'in',
                'quantity' => $qtyProyektor,
                'description' => 'Stok awal proyektor'
            ]);

            for ($i = 1; $i <= $qtyProyektor; ++$i) {
                ItemUnit::create([
                    'item_id' => $proyektor->id,
                    'serial_number' => 'PRY-' . now()->format('Ymd') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'condition' => 'good',
                    'status' => 'available',
                ]);
            }
        });
    }
}
