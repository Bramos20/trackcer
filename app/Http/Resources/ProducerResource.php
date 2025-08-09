<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProducerResource extends JsonResource
{
    protected $fields;
    
    public function __construct($resource, $fields = 'full')
    {
        parent::__construct($resource);
        $this->fields = $fields;
    }
    
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        switch ($this->fields) {
            case 'minimal':
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'image_url' => $this->image_url,
                ];
                
            case 'basic':
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'image_url' => $this->image_url,
                    'total_tracks' => $this->total_tracks ?? 0,
                    'is_followed' => $this->is_followed ?? false,
                    'is_favorite' => $this->is_favorite ?? false,
                ];
                
            case 'full':
            default:
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'discogs_id' => $this->discogs_id,
                    'image_url' => $this->image_url,
                    'created_at' => $this->created_at,
                    'updated_at' => $this->updated_at,
                    'total_tracks' => $this->total_tracks ?? 0,
                    'total_minutes' => $this->total_minutes ?? 0,
                    'average_popularity' => $this->average_popularity ?? 0,
                    'genre_breakdown' => $this->genre_breakdown ?? [],
                    'is_followed' => $this->is_followed ?? false,
                    'is_favorite' => $this->is_favorite ?? false,
                    'analytics' => $this->when(isset($this->analytics), $this->analytics),
                    'collaborators' => $this->when(isset($this->collaborators), $this->collaborators),
                    'recent_tracks' => $this->when(isset($this->recent_tracks), $this->recent_tracks),
                ];
        }
    }
    
    /**
     * Create a collection with field selection
     */
    public static function collectionWithFields($resource, $fields = 'full')
    {
        return $resource->map(function ($item) use ($fields) {
            return new static($item, $fields);
        });
    }
}