<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Simple immutable-ish Book value object used across repository -> service -> controller.
 */
final class Book
{
    public int $id;
    public string $title;
    public string $description;
    public string $author;
    public float $price;
    public string $genre;
    public string $image;
    public ?string $createdAt;
    public ?string $updatedAt;
    public string $status;

    /**
     * Construct a Book instance.
     */
    public function __construct(
        int $id,
        string $title,
        string $description = '',
        string $author = '',
        float $price = 0.0,
        string $genre = '',
        string $image = '',
        ?string $createdAt = null,
        ?string $updatedAt = null,
        string $status = 'active'
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->author = $author;
        $this->price = $price;
        $this->genre = $genre;
        $this->image = $image;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->status = $status;
    }

    /**
     * Create Book from a DB row / associative array.
     *
     * Accepts both migrated column names (book_id, book_title, etc.) and
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // Support both column sets: book_* and id/title style
        $id = (int) ($data['book_id'] ?? $data['id'] ?? 0);
        $title = (string) ($data['book_title'] ?? $data['title'] ?? '');
        $description = (string) ($data['book_description'] ?? $data['description'] ?? '');
        $author = (string) ($data['book_author'] ?? $data['author'] ?? '');
        $price = (float) ($data['book_price'] ?? $data['price'] ?? 0.0);
        $genre = (string) ($data['book_genre'] ?? $data['genre'] ?? '');
        $image = (string) ($data['book_image'] ?? $data['image'] ?? '');
        $createdAt = isset($data['created_at']) ? (string)$data['created_at'] : ($data['createdAt'] ?? null);
        $updatedAt = isset($data['updated_at']) ? (string)$data['updated_at'] : ($data['updatedAt'] ?? null);
        $status = (string) ($data['status'] ?? 'active');

        return new self($id, $title, $description, $author, $price, $genre, $image, $createdAt, $updatedAt, $status);
    }

    /**
     * Convert Book object to array suitable for views (safe to pass to templates).
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'book_id' => $this->id,
            'id' => $this->id,
            'book_title' => $this->title,
            'title' => $this->title,
            'book_description' => $this->description,
            'description' => $this->description,
            'book_author' => $this->author,
            'author' => $this->author,
            'book_price' => $this->price,
            'price' => $this->price,
            'book_genre' => $this->genre,
            'genre' => $this->genre,
            'book_image' => $this->image,
            'image' => $this->image,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'status' => $this->status,
        ];
    }
}
