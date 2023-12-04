<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LearningInfoResourceOne extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'category'=>$this->category
            ?[
                'name_category'=> $this->category->name,
            ] :null,
            'images' => $this->images
                ? $this->images->map(function ($image) {
                    return [
                        'image_id'=> $image->id,
                        'image_url' => $image->image_url,
                    ];
                })
                : null,
            'videos' => $this->videos
                ? [
                    'video_url' => $this->videos->video_url,
                ]
                : null,
            'text_audios' => $this->text_audios
                ? $this->text_audios->map(function ($audio) {
                    return [
                        'audios_id'=> $audio->id, 
                        'audio_url' => $audio->audio_url,
                        'text' => $audio->text,
                    ];
                })->all()
                : null,
            
            'qr_info_associations' => $this->qrInfoAssociations->map(function ($association) {
                return [
                    'qr_identifier' => $association->qr_identifier,
                ];
            }),

            'trivias' => $this->trivias
                ? [
                    'id' =>$this->trivias->id,
                    'name' => $this->trivias->name,
                    'description' => $this->trivias->description,
                ]
                : null,
        ];
    }
}
