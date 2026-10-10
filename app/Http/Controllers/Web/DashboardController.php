<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\DashboardController as ApiDashboardController;
use App\Models\Project;
use App\Models\Task;
use App\Support\Access;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ApiDashboardController $api): View
    {
        $user = $request->user();
        // Reuse the same role-based statistics as the API dashboard.
        $metrics = $api($request)->getData(true);
        $tasks = [];
        if ($user->can('tasks.view')) {
            $query = Task::query()->with('project');
            if (! Access::isHr($user)) {
                $ids = Access::managedProjectIds($user);
                $ownId = $user->employee?->id;
                $query->where(fn ($q) => $q->where('assigned_to', $ownId ?? 0)->orWhereIn('project_id', $ids));
            }
            $tasks = $query->latest('id')->limit(6)->get()->all();
        }

        $projects = [];
        if ($user->can('projects.view')) {
            $query = Project::query();
            if (! Access::isHr($user)) $query->whereIn('id', Access::managedProjectIds($user));
            $projects = $query->latest('id')->limit(3)->get()->all();
        }

        return view('rems.dashboard.index', compact('metrics', 'tasks', 'projects'));
    }
}
