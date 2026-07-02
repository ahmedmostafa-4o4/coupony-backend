<?php

namespace Tests\Unit;

use Tests\TestCase;

class ProductLocalizationCoverageTest extends TestCase
{
    public function test_product_response_messages_are_not_hardcoded(): void
    {
        $files = [
            base_path('app/Application/Http/Controllers/API/V1/ProductShareController.php'),
            base_path('app/Application/Http/Controllers/API/V1/OfferClaimController.php'),
            base_path('app/Application/Http/Controllers/API/V1/StoreOfferClaimController.php'),
            base_path('app/Application/Http/Controllers/API/V1/Admin/AdminOfferClaimController.php'),
            base_path('app/Application/Http/Controllers/API/V1/Admin/ProductManagementController.php'),
            base_path('app/Application/Http/Controllers/API/V1/Admin/ProductRevisionManagementController.php'),
        ];

        $violations = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);

            preg_match_all(
                '/[\'"]message[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/',
                $source,
                $matches,
                PREG_OFFSET_CAPTURE
            );

            foreach ($matches[1] as [$message, $offset]) {
                $line = substr_count(substr($source, 0, $offset), "\n") + 1;
                $violations[] = sprintf('%s:%d "%s"', str_replace(base_path().DIRECTORY_SEPARATOR, '', $file), $line, $message);
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_product_offer_domain_errors_are_not_literal_english(): void
    {
        $files = [
            base_path('app/Application/Http/Controllers/API/V1/Admin/ProductManagementController.php'),
            base_path('app/Domain/Product/Actions/CreateOfferClaim.php'),
            base_path('app/Domain/Product/Actions/RedeemOfferClaim.php'),
        ];

        $disallowedMessages = [
            'Products retrieved successfully.',
            'Product details retrieved successfully.',
            'Product created successfully.',
            'Failed to create product.',
            'Product updated successfully.',
            'Failed to update product.',
            'Product deleted successfully.',
            'Failed to delete product.',
            'Only approved active products can be claimed.',
            'This product does not have an offer available for claiming.',
            'This offer is not active.',
            'This offer is not yet claimable.',
            'This offer is no longer claimable.',
            'Claim limit reached.',
            'Offer claims exhausted.',
            'At least one active variant must be selected for this claim.',
            'The selected claim variant is invalid.',
            'The scanned claim could not be found for this store.',
            'This claim has already been redeemed.',
            'This claim is not redeemable.',
            'One or more claimed variants are no longer redeemable.',
            'Insufficient stock is available to redeem this claim.',
            'This claim has expired.',
        ];

        $violations = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);

            foreach ($disallowedMessages as $message) {
                if (str_contains($source, $message)) {
                    $violations[] = sprintf('%s contains "%s"', str_replace(base_path().DIRECTORY_SEPARATOR, '', $file), $message);
                }
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_arabic_product_locale_values_are_translated(): void
    {
        $locale = require base_path('lang/ar/api.php');
        $keys = [
            'variants.created',
            'variants.create_failed',
            'variants.retrieved',
            'variants.details_retrieved',
            'variants.updated',
            'variants.update_failed',
            'variants.deleted',
            'variants.delete_failed',
            'attributes.updated',
            'attributes.update_failed',
            'images.created',
            'images.create_failed',
            'images.retrieved',
            'images.deleted',
            'images.delete_failed',
            'images.reordered',
            'images.reorder_failed',
            'images.primary_updated',
            'images.primary_update_failed',
        ];

        $untranslated = [];

        foreach ($keys as $key) {
            $value = $this->arrayGet($locale, $key);

            if (! is_string($value) || ! preg_match('/\p{Arabic}/u', $value)) {
                $untranslated[$key] = $value;
            }
        }

        $this->assertSame([], $untranslated);
    }

    public function test_product_request_validation_messages_are_not_hardcoded(): void
    {
        $files = [
            base_path('app/Application/Http/Requests/CreateProductRequest.php'),
            base_path('app/Application/Http/Requests/UpdateProductRequest.php'),
            base_path('app/Application/Http/Requests/AdminStoreProductRequest.php'),
            base_path('app/Application/Http/Requests/AdminUpdateProductRequest.php'),
            base_path('app/Application/Http/Requests/CreateProductVariantRequest.php'),
            base_path('app/Application/Http/Requests/UpdateProductVariantRequest.php'),
            base_path('app/Application/Http/Requests/CreateOfferClaimRequest.php'),
            base_path('app/Application/Http/Requests/Admin/ImportProductsRequest.php'),
        ];

        $violations = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            $patterns = [
                '/errors\(\)->add\([^,]+,\s*[\'"]([^\'"]+)[\'"]/',
                '/public function messages\(\): array\s*\{.*?return\s*\[(.*?)\];\s*\}/s',
            ];

            preg_match_all($patterns[0], $source, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[1] as [$message, $offset]) {
                $line = substr_count(substr($source, 0, $offset), "\n") + 1;
                $violations[] = sprintf('%s:%d "%s"', str_replace(base_path().DIRECTORY_SEPARATOR, '', $file), $line, $message);
            }

            if (preg_match($patterns[1], $source, $messagesMethod)) {
                preg_match_all('/=>\s*[\'"]([^\'"]*[A-Za-z][^\'"]*)[\'"]/', $messagesMethod[1], $messageLiterals, PREG_OFFSET_CAPTURE);

                foreach ($messageLiterals[1] as [$message, $offset]) {
                    $absoluteOffset = strpos($source, $messagesMethod[1]) + $offset;
                    $line = substr_count(substr($source, 0, $absoluteOffset), "\n") + 1;
                    $violations[] = sprintf('%s:%d "%s"', str_replace(base_path().DIRECTORY_SEPARATOR, '', $file), $line, $message);
                }
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_product_request_validation_keys_exist_in_english_and_arabic(): void
    {
        $files = [
            base_path('app/Application/Http/Requests/CreateProductRequest.php'),
            base_path('app/Application/Http/Requests/UpdateProductRequest.php'),
            base_path('app/Application/Http/Requests/AdminStoreProductRequest.php'),
            base_path('app/Application/Http/Requests/AdminUpdateProductRequest.php'),
            base_path('app/Application/Http/Requests/CreateProductVariantRequest.php'),
            base_path('app/Application/Http/Requests/UpdateProductVariantRequest.php'),
            base_path('app/Application/Http/Requests/CreateOfferClaimRequest.php'),
            base_path('app/Application/Http/Requests/Admin/ImportProductsRequest.php'),
        ];
        $locales = [
            'en' => require base_path('lang/en/validation.php'),
            'ar' => require base_path('lang/ar/validation.php'),
        ];
        $missing = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);

            preg_match_all('/__\(\s*[\'"](validation\.[^\'"]+)[\'"]/', $source, $matches);

            foreach (array_unique($matches[1]) as $key) {
                foreach ($locales as $locale => $messages) {
                    if ($this->arrayGet($messages, substr($key, strlen('validation.'))) === null) {
                        $missing[] = sprintf('%s is missing %s in %s', $key, $locale, str_replace(base_path().DIRECTORY_SEPARATOR, '', $file));
                    }
                }
            }
        }

        $this->assertSame([], $missing);
    }

    private function arrayGet(array $array, string $key): mixed
    {
        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return null;
            }

            $array = $array[$segment];
        }

        return $array;
    }
}
