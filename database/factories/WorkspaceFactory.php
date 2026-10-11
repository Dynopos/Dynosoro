<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Workspace> */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    public function definition(): array
    {
        $name = 'Bisnes '.Str::random(6);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'plan' => 'pelanggan',
        ];
    }
}
