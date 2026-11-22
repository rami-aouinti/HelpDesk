<?php

namespace App\Http\Controllers;

use App\Automation\Application\AutomationRuleEngine;
use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AutomationRuleController extends Controller
{
    public function index(): View
    {
        $rules = AutomationRule::query()->with('creator')->withCount('executions')->latest()->paginate(20);
        $executions = AutomationExecution::query()->with(['rule', 'ticket'])->latest()->limit(30)->get();

        return view('automations.index', compact('rules', 'executions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $rule = new AutomationRule([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger' => $data['trigger'],
            'conditions' => $data['conditions'],
            'actions' => $data['actions'],
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $request->user()->id,
        ]);
        $rule->save();

        return back()->with('success', 'Automation rule created.');
    }

    public function update(Request $request, AutomationRule $automationRule): RedirectResponse
    {
        $automationRule->update($this->validated($request));
        return back()->with('success', 'Automation rule updated.');
    }

    public function retry(AutomationExecution $execution, AutomationRuleEngine $engine): RedirectResponse
    {
        abort_unless($execution->status === 'failed', 409);
        $rule = $execution->rule;
        $ticket = $execution->ticket;
        if (! $rule instanceof AutomationRule || ! $ticket instanceof \\App\\Models\\Ticket || ! $rule->is_active) {
            abort(404);
        }

        $engine->retry($rule, $ticket->fresh() ?? $ticket, [
            'retry_of_execution' => $execution->id,
        ]);

        return back()->with('success', 'Automation retry executed. See the new execution in the history.');
    }

    public function destroy(AutomationRule $automationRule): RedirectResponse
    {
        $automationRule->delete();
        return back()->with('success', 'Automation rule deleted.');
    }

    public function toggle(AutomationRule $automationRule): RedirectResponse
    {
        $automationRule->update(['is_active' => ! $automationRule->is_active]);
        return back()->with('success', 'Automation rule status updated.');
    }

    /**
     * @return array{
     *     name: string,
     *     description?: string|null,
     *     trigger: string,
     *     conditions: array<string, mixed>,
     *     actions: list<array<string, mixed>>,
     *     is_active?: bool|int|string
     * }
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'trigger' => ['required', Rule::in(['ticket.created', 'ticket.priority_changed', 'ticket.sla_breached'])],
            'conditions' => ['required', 'array'],
            'conditions.logic' => ['required', Rule::in(['and', 'or'])],
            'conditions.rules' => ['required', 'array', 'min:1', 'max:20'],
            'actions' => ['required', 'array', 'min:1', 'max:10'],
            'actions.*.type' => ['required', Rule::in(['assign_agent', 'set_priority', 'add_tag', 'notify_assignee'])],
            'actions.*.value' => ['nullable'],
            'actions.*.message' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->validateConditionGroup($data['conditions']);

        foreach ($data['actions'] as $action) {
            if (($action['type'] ?? '') === 'assign_agent') {
                validator($action, ['value' => ['required', 'integer', 'exists:users,id']])->validate();
            } elseif (($action['type'] ?? '') === 'set_priority') {
                validator($action, ['value' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])]])->validate();
            } elseif (($action['type'] ?? '') === 'add_tag') {
                validator($action, ['value' => ['required', 'string', 'max:60']])->validate();
            }
        }

        return $data;
    }

    private function validateConditionGroup(array $group): void
    {
        validator($group, [
            'logic' => ['required', Rule::in(['and', 'or'])],
            'rules' => ['required', 'array', 'min:1', 'max:20'],
        ])->validate();

        foreach ($group['rules'] as $condition) {
            if (isset($condition['rules']) && is_array($condition['rules'])) {
                $this->validateConditionGroup($condition);
                continue;
            }

            validator($condition, [
                'field' => ['required', Rule::in(['status', 'priority', 'team_id', 'assigned_to', 'user_id'])],
                'operator' => ['required', Rule::in(['equals', 'not_equals', 'in', 'contains', 'greater_than', 'less_than'])],
                'value' => ['present'],
            ])->validate();
        }
    }
}
