<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('store_employees')) {
            return;
        }

        if (! Schema::hasColumn('store_employees', 'address_id')) {
            Schema::table('store_employees', function (Blueprint $table) {
                $table->unsignedBigInteger('address_id')->nullable()->after('user_id');
            });
        }

        if (! $this->hasAddressForeignKey()) {
            Schema::table('store_employees', function (Blueprint $table) {
                $table->foreign('address_id')
                    ->references('id')
                    ->on('addresses')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('store_employees') || ! Schema::hasColumn('store_employees', 'address_id')) {
            return;
        }

        $hasAddressForeignKey = $this->hasAddressForeignKey();

        Schema::table('store_employees', function (Blueprint $table) use ($hasAddressForeignKey) {
            if ($hasAddressForeignKey) {
                $table->dropForeign(['address_id']);
            }

            $table->dropColumn('address_id');
        });
    }

    private function hasAddressForeignKey(): bool
    {
        $driver = DB::getDriverName();
        $database = DB::getDatabaseName();

        if ($driver === 'mysql') {
            return DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', 'store_employees')
                ->where('COLUMN_NAME', 'address_id')
                ->where('REFERENCED_TABLE_NAME', 'addresses')
                ->exists();
        }

        if ($driver === 'sqlite') {
            return collect(DB::select('PRAGMA foreign_key_list(store_employees)'))
                ->contains(fn ($foreignKey) => $foreignKey->from === 'address_id' && $foreignKey->table === 'addresses');
        }

        if ($driver === 'pgsql') {
            return DB::table('information_schema.key_column_usage as kcu')
                ->join('information_schema.constraint_column_usage as ccu', function ($join) {
                    $join->on('kcu.constraint_name', '=', 'ccu.constraint_name')
                        ->on('kcu.constraint_schema', '=', 'ccu.constraint_schema');
                })
                ->where('kcu.table_schema', 'public')
                ->where('kcu.table_name', 'store_employees')
                ->where('kcu.column_name', 'address_id')
                ->where('ccu.table_name', 'addresses')
                ->exists();
        }

        return false;
    }
};
