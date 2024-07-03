<?php

namespace NadLambino\Uploadable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait Events
{
    protected function onDelete(Model $model): void
    {
        try {
            DB::beginTransaction();

            $deleteMethod = config('uploadable.force_delete_uploads') === true ? 'forceDelete' : 'delete';
            $model->uploads()->get()->each(fn ($upload) => $upload->$deleteMethod());

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    protected function onRestore(Model $model): void
    {
        $model->uploads()->restore();
    }
}
