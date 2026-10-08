<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function creating(Model $model): void
    {
        if (auth()->check()) {
            $model->created_by ??= auth()->id();
            $model->updated_by ??= auth()->id();
        }
    }

    public function updating(Model $model): void
    {
        if (auth()->check()) {
            $model->updated_by = auth()->id();
        }
    }
}
