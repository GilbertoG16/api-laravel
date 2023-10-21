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
            'images' => $this->images
                ? $this->images->map(function ($image) {
                    return [
                        'image_url' => $image->image_url,
                        // Otros campos de Image si es necesario
                    ];
                })
                : null,
            'videos' => $this->videos
                ? [
                    'video_url' => $this->videos->video_url,
                    // Otros campos de Video si es necesario
                ]
                : null,
            'text_audios' => $this->text_audios
                ? $this->text_audios->map(function ($audio) {
                    return [
                        'audio_url' => $audio->audio_url,
                        // Otros campos de TextAudio si es necesario
                    ];
                })->all()
                : null,
            
            'qr_info_associations' => $this->qrInfoAssociations->map(function ($association) {
                return [
                    'qr_identifier' => $association->qr_identifier,
                ];
            }),
        ];
    }
}
