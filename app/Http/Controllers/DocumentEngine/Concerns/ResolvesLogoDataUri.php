<?php

namespace App\Http\Controllers\DocumentEngine\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

trait ResolvesLogoDataUri
{
    /**
     * Embeds the accountant's logo as a base64 data URI so the same template
     * renders correctly both through DomPDF and as plain HTML (printView()).
     */
    protected function logoDataUriFor(User $user): ?string
    {
        if (! $user->logo_path || ! Storage::disk('public')->exists($user->logo_path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($user->logo_path);

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($user->logo_path));
    }
}
