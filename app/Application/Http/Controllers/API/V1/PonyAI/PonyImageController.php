<?php

namespace App\Application\Http\Controllers\API\V1\PonyAI;

use App\Application\Http\Controllers\Controller;
use App\Domain\PonyAI\Models\PonyMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PonyImageController extends Controller
{
    /**
     * Serve a stored image-query upload via a Laravel temporary signed URL.
     *
     * Access is gated on three layers:
     *  1. The URL must carry a valid Laravel signature (enforced by the `signed`
     *     middleware on the route - tampering or expiry returns 403).
     *  2. The PonyMessage must actually carry an image attachment.
     *  3. The file must still exist on the LOCAL disk (purged uploads return 404).
     *
     * The disk is `local` not `public`, so the file is never served via a
     * static asset URL - this endpoint is the only way to reach it.
     */
    public function show(Request $request, PonyMessage $message): Response
    {
        $attachments = $message->attachments;

        if (! is_array($attachments) || empty($attachments['image'])) {
            abort(404);
        }

        $path = (string) $attachments['image'];

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $mime = is_string($attachments['mime'] ?? null) && $attachments['mime'] !== ''
            ? (string) $attachments['mime']
            : (string) (Storage::disk('local')->mimeType($path) ?: 'application/octet-stream');

        return Storage::disk('local')->response(
            $path,
            basename($path),
            [
                'Content-Type' => $mime,
                'Cache-Control' => 'private, max-age=60',
            ],
        );
    }
}
