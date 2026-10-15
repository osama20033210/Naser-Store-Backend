<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'email' => $this->email,
            'account_type' => $this->account_type,
            'roles' => $this->account_type === 'customer' ? [] : $this->getRoleNames()->values(),
            'permissions' => $this->account_type === 'customer' ? [] : $this->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
