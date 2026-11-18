<?php

namespace App\Http\Controllers;

use App\Exceptions\MediaLifecycleException;
use App\Http\PrivatePhotoResponse;
use App\Services\Media\PhotoLifecycleService;
use App\Services\Programs\ProgramActivityPhotoService;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ProgramActivityPhotoController extends Controller
{
    public function __construct(private ProgramActivityPhotoService $gallery, private PhotoLifecycleService $photos) {}

    public function index(Request $request, string $activity): Response
    {
        return $this->respond(function () use ($request, $activity) {
            $manager = ! $request->routeIs('association.*');
            $gallery = $this->gallery->gallery($this->gallery->parent($activity), $this->gallery->authorize($request, $activity, false), $manager);
            $prefix = $request->routeIs('admin.*') ? 'admin' : ($manager ? 'field-officer' : 'association');
            foreach ($gallery['photos'] as &$photo) {
                foreach (['thumbnail', 'display'] as $variant) {
                    $photo[$variant.'_url'] = route($prefix.'.program-activities.photos.image', ['activity' => $activity, 'photo' => $photo['id'], 'variant' => $variant]);
                }
            }
            return response()->json($gallery, 200, PrivatePhotoResponse::HEADERS);
        });
    }

    public function store(Request $request, string $activity): Response
    {
        return $this->respond(function () use ($request, $activity) {
            $parent = $this->gallery->parent($activity);
            $authorize = $this->gallery->authorize($request, $activity, true);
            DB::transaction(fn () => $authorize($parent));
            $data = $request->validate(['photo' => ['required', 'file', 'max:5120'], 'request_id' => ['required', 'uuid']]);
            $reservation = $this->photos->reserve($parent, $data['photo'], $data['request_id'], $request, $authorize);
            if (in_array($reservation['status'], ['deleting', 'deleted'], true)) throw ValidationException::withMessages(['photo' => 'This upload was removed. Choose the photo again.']);
            return response()->json(['photo' => $this->photos->upload($reservation['id'], $data['photo'], $request, $authorize)], 200, PrivatePhotoResponse::HEADERS);
        });
    }

    public function reorder(Request $request, string $activity): Response
    {
        return $this->respond(function () use ($request, $activity) {
            $data = $request->validate(['ids' => ['present', 'array', 'max:20'], 'ids.*' => ['required', 'uuid', 'distinct']]);
            $this->photos->reorder($this->gallery->parent($activity), $data['ids'], $request, $this->gallery->authorize($request, $activity, true));
            return response()->json(['message' => 'Photo order saved.'], 200, PrivatePhotoResponse::HEADERS);
        });
    }

    public function destroy(Request $request, string $activity, string $photo): Response
    {
        return $this->respond(function () use ($request, $activity, $photo) {
            $this->gallery->photo($activity, $photo);
            $authorize = $this->gallery->authorize($request, $activity, true);
            $this->photos->withdraw($photo, $request, $authorize);
            return response()->json(['status' => $this->photos->reconcile($photo, $request, $authorize)['status']], 200, PrivatePhotoResponse::HEADERS);
        });
    }

    public function reconcile(Request $request, string $activity, string $photo): Response
    {
        return $this->respond(function () use ($request, $activity, $photo) {
            $this->gallery->photo($activity, $photo);
            return response()->json(['status' => $this->photos->reconcile($photo, $request, $this->gallery->authorize($request, $activity, true))['status']], 200, PrivatePhotoResponse::HEADERS);
        });
    }

    public function image(Request $request, string $activity, string $photo, string $variant): Response
    {
        return $this->respond(function () use ($request, $activity, $photo, $variant) {
            $this->gallery->photo($activity, $photo);
            return PrivatePhotoResponse::stream($this->photos->open($photo, $variant, $this->gallery->authorize($request, $activity, false)), 'activity-photo');
        });
    }

    private function respond(Closure $work): Response
    {
        try { return $work(); }
        catch (ValidationException $error) { return response()->json(['message' => 'Check the photo request.', 'errors' => $error->errors()], $error->status, PrivatePhotoResponse::HEADERS); }
        catch (MediaLifecycleException $error) {
            [$status, $message] = match ($error->reason) {
                'capacity_reached' => [409, 'All 20 photo slots are in use, including pending uploads.'],
                'request_conflict', 'operation_pending', 'order_conflict' => [409, 'The gallery changed or an upload is pending. Refresh and try again.'],
                'invalid_photo', 'photo_not_ready' => [404, 'Photo unavailable.'],
                default => [503, 'Photo storage is unavailable. Refresh the gallery before retrying.'],
            };
            return response()->json(['message' => $message], $status, PrivatePhotoResponse::HEADERS);
        }
        catch (ModelNotFoundException) { return response()->json(['message' => 'Photo unavailable.'], 404, PrivatePhotoResponse::HEADERS); }
        catch (HttpExceptionInterface $error) { return response()->json(['message' => 'Photo access is unavailable.'], $error->getStatusCode(), PrivatePhotoResponse::HEADERS); }
        catch (Throwable $error) {
            Log::warning('Activity gallery unavailable.', ['type' => $error::class]);
            return response()->json(['message' => 'Photos are temporarily unavailable.'], 503, PrivatePhotoResponse::HEADERS);
        }
    }
}
