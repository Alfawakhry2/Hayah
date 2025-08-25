<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResource extends JsonResource
{
    public int $status_code;
    public string $message;
    public mixed $data;

    public function __construct(
        int $status_code,
        string $message,
        mixed $data = null,
    ) {
        parent::__construct(null);
        $this->status_code = $status_code;
        $this->message = $message;
        $this->data = $data;
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status_code' => $this->status_code,
            'message' => $this?->message,
            'data' => $this?->data,
        ];
    }

    public function withResponse($request, $response)
    {
        // dd($this->status_code);
        $response->setStatusCode($this->status_code);
    }
}
