<?php

namespace Tests\Unit;

use App\Domain\Product\DTOs\ProductData;
use App\Domain\Product\Support\ArabicSlugTransliterator;
use App\Domain\Product\Support\IdentifierCodeResolver;
use App\Domain\Product\Support\PrepareProductIdentifiers;
use App\Domain\Product\Support\VariantSkuGenerator;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductIdentifierGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
    }

    public function test_transliterator_handles_arabic_and_english_titles(): void
    {
        $transliterator = app(ArabicSlugTransliterator::class);

        $this->assertSame('gazma', $transliterator->transliterate('جزمة'));
        $this->assertSame('gazma-ryady', $transliterator->transliterate('جزمة رياضي'));
        $this->assertSame('running-shoes', $transliterator->transliterate('Running Shoes'));
    }

    public function test_prepare_product_identifiers_generates_expected_product_and_variant_skus(): void
    {
        $store = $this->storeForSeller();

        /** @var PrepareProductIdentifiers $prepare */
        $prepare = app(PrepareProductIdentifiers::class);
        $prepared = $prepare->forCreate($store, new ProductData(
            attributes: [
                'title' => 'جزمة',
                'slug' => null,
                'currency' => 'EGP',
                'sku' => null,
            ],
            categoryIds: [],
            images: [],
            variants: [[
                'title' => 'Black / 42',
                'option_summary' => 'Color: Black, Size: 42',
                'sku' => null,
                'barcode' => null,
                'original_price' => 110,
                'currency' => 'EGP',
                'sort_order' => 0,
                'is_default' => true,
                'is_active' => true,
                'inventory_mode' => 'tracked',
                'stock_qty' => 8,
                'low_stock_threshold' => 2,
                'allow_backorder' => false,
                'attributes' => [
                    ['attribute_name' => 'color', 'attribute_value' => 'black', 'sort_order' => 0],
                    ['attribute_name' => 'size', 'attribute_value' => '42', 'sort_order' => 1],
                ],
            ]],
            offer: [
                'type' => 'fixed',
                'status' => 'active',
                'fixed_amount' => 15,
                'buy_variant_skus' => [],
                'reward_variant_skus' => [],
            ],
            hasCategoryIds: false,
            hasImages: false,
            hasVariants: true,
            hasOffer: true,
        ));

        $this->assertSame('gazma', $prepared->attributes()['slug']);
        $this->assertSame('PRD-SHO-GAZ', $prepared->attributes()['sku']);
        $this->assertSame('VAR-SHO-GAZ-BLK-42', $prepared->variants()[0]['sku']);
    }

    public function test_resolve_attribute_code_maps_common_arabic_values_to_stable_codes(): void
    {
        $resolver = app(IdentifierCodeResolver::class);

        $this->assertSame('BLK', $resolver->resolveAttributeCode('أسود'));
        $this->assertSame('WHT', $resolver->resolveAttributeCode('أبيض'));
        $this->assertSame('BLU', $resolver->resolveAttributeCode('أزرق'));
    }

    public function test_variant_sku_generation_supports_arabic_attribute_names_and_values(): void
    {
        $generator = app(VariantSkuGenerator::class);

        $variants = $generator->generateMany([[
            'title' => 'Black / 42',
            'sku' => null,
            'attributes' => [
                ['attribute_name' => 'لون', 'attribute_value' => 'أسود', 'sort_order' => 0],
                ['attribute_name' => 'مقاس', 'attribute_value' => '42', 'sort_order' => 1],
            ],
        ]], 'جزمة');

        $this->assertSame('VAR-SHO-GAZ-BLK-42', $variants[0]['sku']);
    }

    private function storeForSeller(): Store
    {
        $seller = User::factory()->create();
        $seller->assignRole('seller');

        return Store::factory()->create([
            'owner_user_id' => $seller->id,
        ]);
    }
}
