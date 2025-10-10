<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
        $this->middleware(function ($request, $next) {
            abort_if($request->user()->role !== 'consultant', 403);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $tasks = Task::where('user_id', $request->user()->id)
            ->latest()->paginate(12);

        return view('consultants.tasks.index', compact('tasks'));
    }

    public function create()
    {
        return view('consultants.tasks.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
        ]);

        $data['user_id'] = $request->user()->id;

        Task::create($data);

        return redirect()->route('consultants.tasks.index')->with('status', 'Tarefa criada.');
    }

    public function edit(Task $task)
    {
        $this->authorizeTask($task);

        return view('consultants.tasks.edit', compact('task'));
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'done' => ['nullable', 'boolean'],
        ]);

        $task->update($data);

        return redirect()->route('consultants.tasks.index')->with('status', 'Tarefa atualizada.');
    }

    public function destroy(Task $task)
    {
        $this->authorizeTask($task);
        $task->delete();

        return back()->with('status', 'Tarefa removida.');
    }

    private function authorizeTask(Task $task)
    {
        abort_if($task->user_id !== auth()->id(), 403);
    }
}
