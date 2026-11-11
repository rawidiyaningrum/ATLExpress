<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'pic_id' => User::factory(),
            'status' => Project::STATUS_BACKLOG,
            'priority' => Project::PRIORITY_NORMAL,
            'start_date' => now()->toDateString(),
            'due_date' => now()->addWeeks(2)->toDateString(),
            'progress_percent' => null,
            'sort_order' => 0,
            'created_by' => User::factory(),
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function priority(string $priority): static
    {
        return $this->state(fn (): array => ['priority' => $priority]);
    }
}
