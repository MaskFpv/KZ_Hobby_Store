<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori artikel jadi tabel (bisa ditambah dari panel), menggantikan enum articles.category.
 * Artikel lama dipetakan ke kategori dengan slug yang sama. Tambah kolom is_pinned (sorotan).
 */
return new class extends Migration
{
    private const DEFAULTS = [
        // [slug, nama, kanji, warna]
        ['toko', 'Info Toko', '店', '#d76d77'],
        ['anime', 'Anime', '画', '#ff2e88'],
        ['hobi', 'Hobi & Koleksi', '趣', '#14b8a6'],
        ['event', 'Event', '祭', '#f59e0b'],
        ['action-figure', 'Action Figure', '像', '#8b5cf6'],
        ['gunpla', 'Gunpla', '模', '#3b82f6'],
        ['manga', 'Manga & Light Novel', '漫', '#ef4444'],
        ['review', 'Review', '評', '#22c55e'],
        ['tips', 'Tips & Tutorial', '技', '#06b6d4'],
    ];

    public function up(): void
    {
        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('slug', 70)->unique();
            $table->string('kanji', 4)->nullable();
            $table->string('color', 7)->default('#ff2e88');
            $table->string('description', 200)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        foreach (self::DEFAULTS as $i => [$slug, $name, $kanji, $color]) {
            DB::table('article_categories')->insert([
                'slug' => $slug, 'name' => $name, 'kanji' => $kanji, 'color' => $color,
                'sort_order' => $i, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('article_category_id')->nullable()->after('slug')->constrained('article_categories')->nullOnDelete();
            $table->boolean('is_pinned')->default(false)->after('status');
        });

        foreach (DB::table('article_categories')->pluck('id', 'slug') as $slug => $id) {
            DB::table('articles')->where('category', $slug)->update(['article_category_id' => $id]);
        }

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->enum('category', ['toko', 'anime', 'hobi', 'event'])->default('anime')->after('slug');
        });
        foreach (DB::table('article_categories')->whereIn('slug', ['toko', 'anime', 'hobi', 'event'])->pluck('slug', 'id') as $id => $slug) {
            DB::table('articles')->where('article_category_id', $id)->update(['category' => $slug]);
        }
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_category_id');
            $table->dropColumn('is_pinned');
        });
        Schema::dropIfExists('article_categories');
    }
};
