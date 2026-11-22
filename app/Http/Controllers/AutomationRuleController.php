<?php

namespace App\Http\Controllers;

use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
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
        $data['created_by'] = $request->user()->id;
        AutomationRule::query()->create($data);

        return back()->with('success', 'Automation rule created.');
    }

    public function update(Request $request, AutomationRule $automationRule): RedirectResponse
    {
        $automationRule->update($this->validated($request));
        return back()->with('success', 'Automation rule updated.');
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

        foreach ($data['conditions']['rules'] as $condition) {
            validator($condition, [
                'field' => ['required', Rule::in(['status', 'priority', 'team_id', 'assigned_to', 'user_id'])],
                'operator' => ['required', Rule::in(['equals', 'not_equals', 'in', 'contains', 'greater_than', 'less_than'])],
                'value' => ['present'],
            ])->validate();
        }

        foreach ($data['actions'] as $action) {
            if (($action['type'] ?? '') === 'assign_agent') {
                validator($action, ['value' => ['required', 'integer', 'exists:users,id']])->validate();
            }
            if (($action['type'] ?? '') === 'set_priority') {
                validator($action, ['value' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])]])->validate();
            }
            if (($action['type'] ?? '') === 'add_tag') {
                validator($action, ['value' => ['required', 'string', 'max:60']])->validate();
            }
        }

        return $data;
    }
}
