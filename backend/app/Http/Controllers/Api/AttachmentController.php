<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Gap;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Anexos ficam no disco "local" (storage/app/private), fora da pasta pública.
 * O download passa pela API, que verifica a permissão antes de entregar o arquivo.
 */
class AttachmentController extends Controller
{
    private const ALLOWED = 'jpg,jpeg,png,gif,webp,pdf,xls,xlsx,csv,doc,docx,txt,zip';

    public function storeForGap(Request $request, Gap $gap): AttachmentResource
    {
        Gate::authorize('attach', $gap);

        return $this->store($request, $gap);
    }

    public function storeForMeeting(Request $request, Meeting $meeting): AttachmentResource
    {
        Gate::authorize('update', $meeting);

        return $this->store($request, $meeting);
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment->attachable);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Request $request, Attachment $attachment)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->id === $attachment->user_id, 403);

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return response()->noContent();
    }

    private function store(Request $request, Model $owner): AttachmentResource
    {
        $request->validate(['file' => ['required', 'file', 'max:20480', 'mimes:'.self::ALLOWED]]);

        $file = $request->file('file');
        $path = $file->store("attachments/{$owner->getMorphClass()}/{$owner->getKey()}", 'local');

        $attachment = $owner->attachments()->create([
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return new AttachmentResource($attachment->load('user:id,name'));
    }
}
