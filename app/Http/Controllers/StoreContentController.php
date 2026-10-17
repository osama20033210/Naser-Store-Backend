<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListRequest;
use App\Http\Requests\StorefrontSettingsRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\StorePostResource;
use App\Models\StorefrontSetting;
use App\Models\StorePost;
use Carbon\CarbonImmutable;

class StoreContentController extends Controller
{
    public function settings()
    {
        return response()->json(['data' => StorefrontSetting::find(1)?->content ?? (object) []]);
    }

    public function saveSettings(StorefrontSettingsRequest $request)
    {
        // One atomic complete settings document; no credentials or arbitrary keys are accepted.
        StorefrontSetting::upsert([['id' => 1, 'content' => json_encode($request->validated(), JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]], ['id'], ['content', 'updated_at']);
        return $this->settings();
    }

    public function posts(ListRequest $request)
    {
        return StorePostResource::collection(StorePost::query()->with('product:id,active,image_path')->where('published', true)->where('published_at', '<=', now())
            ->select(['id', 'slug', 'title_en', 'title_ar', 'excerpt_en', 'excerpt_ar', 'product_id', 'published', 'published_at'])
            ->orderByDesc('published_at')->orderByDesc('id')->paginate($request->integer('per_page', 9)));
    }

    public function post(string $slug)
    {
        return new StorePostResource(StorePost::query()->with('product:id,active,image_path')->where('published', true)->where('published_at', '<=', now())->where('slug', $slug)->firstOrFail());
    }

    public function index(ListRequest $request)
    {
        $query = StorePost::query()->select(['id', 'slug', 'title_en', 'title_ar', 'excerpt_en', 'excerpt_ar', 'product_id', 'published', 'published_at']);
        if ($q = $request->validated('q')) $query->where(fn ($query) => $query->where('title_en', 'ilike', '%'.$q.'%')->orWhere('title_ar', 'ilike', '%'.$q.'%'));
        return StorePostResource::collection($query->latest('id')->paginate($request->integer('per_page', 15)));
    }

    public function show(StorePost $post) { return new StorePostResource($post); }

    public function store(StorePostRequest $request) { return new StorePostResource(StorePost::create($this->postData($request))); }

    public function update(StorePostRequest $request, StorePost $post)
    {
        $post->update($this->postData($request));
        return new StorePostResource($post);
    }

    public function destroy(StorePost $post)
    {
        $post->delete();
        return response()->noContent();
    }

    private function postData(StorePostRequest $request): array
    {
        $data = $request->validated();
        $data['published_at'] = empty($data['published_at']) ? null : CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['published_at'], 'Asia/Damascus')->utc();
        return $data;
    }
}
