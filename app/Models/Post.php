<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category_id',
        'image',
        'content',
        'meta_title',
        'meta_description',
        'published_at',
        'status',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = $post->generateUniqueSlug(Str::slug($post->title));
            }

            if ($post->status === 'published' && empty($post->published_at)) {
                $post->published_at = now();
            }
        });
    }

    protected function generateUniqueSlug(string $slug): string
    {
        $base = $slug;
        $suffix = 2;

        while (static::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                     ->whereNotNull('published_at');
    }
}
