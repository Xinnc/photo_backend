<?php

namespace App\Http\Controllers;


use App\Exceptions\ApiException;
use App\Http\Requests\PhotoShareRequest;
use App\Http\Requests\StorePhotoRequst;
use App\Http\Requests\UpdatePhotoRequest;
use App\Models\Photo;
use App\Models\PhotoUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function store(StorePhotoRequst $request)
    {
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $request->photo);
        $binary = base64_decode($base64, true);

        if ($binary === false) {
            throw new ApiException(422, 'invalid base64 data.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary);
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            default      => throw new ApiException(422, 'Only JPEG and PNG are supported.'),
        };

        $filename = 'photo_' . time() . '.' . $extension;

        $relativePath = 'photos/' . $filename;
        Storage::disk('public')->put($relativePath, $binary);

        $photo = Photo::create([
            'name' => 'Untitled',
            'url' => url(Storage::url($relativePath)),
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'id' => $photo->id,
            'name' => $photo->name,
            'url' => $photo->url,
        ], 201);
    }

    public function update(UpdatePhotoRequest $request, Photo $photo)
    {
        if ($photo->user_id !== auth()->id()) {
            throw new ApiException(403, 'You are not allowed to update this photo.');
        }

        if ($request->name) {
            $photo->name = $request->name;
        }

        if ($request->photo) {
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $request->photo);
            $binary = base64_decode($base64, true);

            if ($binary === false) {
                throw new ApiException(422, 'invalid base64 data.');
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($binary);
            $extension = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                default      => throw new ApiException(422, 'Only JPEG and PNG are supported.'),
            };

            if ($photo->url) {
                $oldPath = str_replace(url('/storage/'), '', $photo->url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $filename = 'photo_' . time() . '_' . uniqid() . '.' . $extension;
            $relativePath = 'photos/' . $filename;
            Storage::disk('public')->put($relativePath, $binary);

            $photo->url = url(Storage::url($relativePath));
        }

        $photo->save();

        return response()->json([
            'id' => $photo->id,
            'name' => $photo->name,
            'url' => $photo->url,
        ]);
    }

    public function index(){
        $photos = Photo::with('users')->get();
        return $photos->map(function ($photo) {
            return [
                'id' => $photo->id,
                'name' => $photo->name,
                'url' => $photo->url,
                'owner_id' => $photo->user_id,
                'users' => $photo->users->pluck('id')->toArray(),
            ];
        });
    }

    public function show(Photo $photo){
        return [
            'id' => $photo->id,
            'name' => $photo->name,
            'url' => $photo->url,
            'owner_id' => $photo->user_id,
            'users' => $photo->users->pluck('id')->toArray(),
        ];
    }

    public function destroy(Photo $photo){
        if($photo->user_id !== auth()->id())throw new ApiException(403, "Forbidden for you");

        $path = 'photos/' . basename($photo->url);

        if(Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $photo->delete();

        return response(null, 204);
    }

    public function share(PhotoShareRequest $request, User $user)
    {
        $photos = $request->photos;
        $existing = [];

        foreach ($photos as $photoId) {

            $photo = Photo::find($photoId);

            if (!$photo || $photo->user_id !== auth()->id()) {
                throw new ApiException(403, "You are not the owner of {$photoId} photo");
            }

            if (PhotoUser::where('user_id', $user->id)->where('photo_id', $photoId)->first())
            {
                $existing[] = $photoId;
                continue;
            }

            PhotoUser::create([
                'user_id' => $user->id,
                'photo_id' => $photoId,
            ]);
        }

        return response()->json([
            'existing_photos' => $existing,
        ], 201);
    }
}
