<?php

namespace App\Repositories;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use App\Base\BaseRepository;
use App\Events\ShowBlog;

class BlogRepository extends BaseRepository
{
    public function model(): string
    {
        return Blog::class;
    }

    public function index(Request $request): Collection
    {
        return $this->model->newQuery()->isPublish()->with('member')->orderBy('id', 'desc')->get();
    }

    public function store(Request $request): Blog
    {
        /** @var Blog $blog */
        $blog = new $this->model();
        $blog
            ->setTitle($request->get('title'))
            ->setContent($request->get('content'))
            ->setMemberId($request->user()->getKey())
            ->save();

        $images = $request->file('images');
        $this->addMediaForBlog($blog, $images);

        return $blog;
    }

    public function show(int $id): ?Blog
    {
        $blog = $this->model->findOrFail($id);
        ShowBlog::dispatch($blog);

        return $this->model->findOrFail($id);
    }

    public function update(Request $request, int $id): Blog
    {
        /** @var Blog $blog */
        $blog = $this->model->newQuery()->findOrFail($id);
        $blog
            ->setTitle($request->get('title'))
            ->setContent($request->get('content'))
            ->save();

        $blog->clearMediaCollection(Blog::BLOG_MEDIA);

        $images = $request->file('images');
        $this->addMediaForBlog($blog, $images);

        return $blog;
    }

    public function destroy(int $id): void
    {
        $this->model->findOrFail($id)->delete();
    }

    public function updateStatus(int $id): Blog
    {
        /** @var Blog $blog */
        $blog = $this->model->findOrFail($id);

        if ($blog->isPublish()) {
            $blog->setStatus(Blog::IS_DRAFT);
        } else {
            $blog->setStatus(Blog::IS_PUBLISH);
        }

        $blog->save();

        return $blog;
    }

    protected function addMediaForBlog(Blog $blog, array $images): void
    {
        /** @var UploadedFile $image */
        foreach ($images as $image) {
            $blog->addMedia($image)->toMediaCollection(Blog::BLOG_MEDIA);
        }
    }
}
