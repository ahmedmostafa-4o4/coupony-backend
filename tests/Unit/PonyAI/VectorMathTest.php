<?php

namespace Tests\Unit\PonyAI;

use App\Domain\PonyAI\Exceptions\PonyAIException;
use App\Domain\PonyAI\Support\VectorMath;
use Tests\TestCase;

class VectorMathTest extends TestCase
{
    public function test_identical_vectors_have_cosine_one(): void
    {
        $this->assertEqualsWithDelta(1.0, VectorMath::cosine([1.0, 2.0, 3.0], [1.0, 2.0, 3.0]), 1e-9);
    }

    public function test_orthogonal_vectors_have_cosine_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, VectorMath::cosine([1.0, 0.0], [0.0, 1.0]), 1e-9);
    }

    public function test_opposite_vectors_have_cosine_minus_one(): void
    {
        $this->assertEqualsWithDelta(-1.0, VectorMath::cosine([1.0, 2.0], [-1.0, -2.0]), 1e-9);
    }

    public function test_zero_vector_returns_zero(): void
    {
        $this->assertSame(0.0, VectorMath::cosine([0.0, 0.0, 0.0], [1.0, 2.0, 3.0]));
        $this->assertSame(0.0, VectorMath::cosine([1.0, 2.0, 3.0], [0.0, 0.0, 0.0]));
    }

    public function test_empty_vectors_return_zero(): void
    {
        $this->assertSame(0.0, VectorMath::cosine([], []));
    }

    public function test_length_mismatch_throws(): void
    {
        $this->expectException(PonyAIException::class);
        $this->expectExceptionMessage('different lengths');

        VectorMath::cosine([1.0, 2.0], [1.0, 2.0, 3.0]);
    }

    public function test_top_k_returns_sorted_by_score_descending(): void
    {
        $query = [1.0, 0.0];
        $candidates = [
            'a' => [1.0, 0.0],   // cosine 1.0
            'b' => [0.0, 1.0],   // cosine 0.0
            'c' => [0.5, 0.5],   // cosine ~0.7071
            'd' => [-1.0, 0.0],  // cosine -1.0
        ];

        $ranked = VectorMath::topK($query, $candidates, 3);

        $this->assertCount(3, $ranked);
        $this->assertSame('a', $ranked[0]['id']);
        $this->assertSame('c', $ranked[1]['id']);
        $this->assertSame('b', $ranked[2]['id']);
        $this->assertEqualsWithDelta(1.0, $ranked[0]['score'], 1e-9);
        $this->assertEqualsWithDelta(M_SQRT1_2, $ranked[1]['score'], 1e-9);
    }

    public function test_top_k_skips_empty_candidate_vectors(): void
    {
        $ranked = VectorMath::topK([1.0, 0.0], [
            'a' => [1.0, 0.0],
            'b' => [],
            'c' => [0.5, 0.5],
        ], 5);

        $this->assertCount(2, $ranked);
        $this->assertEqualsCanonicalizing(['a', 'c'], array_column($ranked, 'id'));
    }

    public function test_top_k_returns_empty_when_top_is_zero(): void
    {
        $this->assertSame([], VectorMath::topK([1.0], ['a' => [1.0]], 0));
    }
}
