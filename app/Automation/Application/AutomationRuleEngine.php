<?php

declare(strict_types=1);

namespace App\Automation\Application;

use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\AutomationRuleNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class AutomationRuleEngine
{
    public function dispatch(string $trigger, Ticket $ticket, array $context = []): void
    {
        AutomationRule::query()
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->orderBy('id')
            ->each(function (AutomationRule $rule) use ($ticket, $trigger, $context): void {
                $execution = AutomationExecution::query()->create([
                    'automation_rule_id' => $rule->id,
                    'ticket_id' => $ticket->id,
                    'trigger' => $trigger,
                    'status' => 'running',
                    'context' => $context,
                    'started_at' => now(),
                ]);

                try {
                    if (! $this->matches($rule->conditions ?? [], $ticket)) {
                        $execution->update(['status' => 'skipped', 'finished_at' => now()]);
                        return;
                    }

                    foreach ($rule->actions ?? [] as $action) {
                        $this->perform($action, $ticket);
                    }

                    $execution->update(['status' => 'succeeded', 'finished_at' => now()]);
                } catch (Throwable $exception) {
                    report($exception);
                    $execution->update([
                        'status' => 'failed',
                        'error' => mb_substr($exception->getMessage(), 0, 6000),
                        'finished_at' => now(),
                    ]);
                }
            });
    }

    private function matches(array $group, Ticket $ticket): bool
    {
        $logic = strtolower((string) ($group['logic'] ?? 'and'));
        $results = [];

        foreach (($group['rules'] ?? []) as $rule) {
            if (isset($rule['rules']) && is_array($rule['rules'])) {
                $results[] = $this->matches($rule, $ticket);
                continue;
            }

            $field = (string) ($rule['field'] ?? '');
            $operator = (string) ($rule['operator'] ?? 'equals');
            $actual = match ($field) {
                'status', 'priority', 'team_id', 'assigned_to', 'user_id' => $ticket->{$field},
                default => null,
            };
            $expected = $rule['value'] ?? null;
            $results[] = match ($operator) {
                'equals' => (string) $actual === (string) $expected,
                'not_equals' => (string) $actual !== (string) $expected,
                'in' => is_array($expected) && in_array((string) $actual, array_map('strval', $expected), true),
                'contains' => is_string($actual) && is_string($expected) && str_contains(mb_strtolower($actual), mb_strtolower($expected)),
                'greater_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
                'less_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
                default => false,
            };
        }

        if ($results === []) {
            return true;
        }

        return $logic === 'or' ? in_array(true, $results, true) : ! in_array(false, $results, true);
    }

    private function perform(array $action, Ticket $ticket): void
    {
        $type = (string) ($action['type'] ?? '');
        $value = $action['value'] ?? null;

        match ($type) {
            'assign_agent' => $this->assignAgent($ticket, (int) $value),
            'set_priority' => $this->setPriority($ticket, (string) $value),
            'add_tag' => $this->addTag($ticket, (string) $value),
            'notify_assignee' => $this->notifyAssignee($ticket, (string) ($action['message'] ?? 'An automation rule was triggered.')),
            default => throw new \InvalidArgumentException("Unsupported automation action: {$type}"),
        };
    }

    private function assignAgent(Ticket $ticket, int $agentId): void
    {
        $agent = User::query()->role('agent')->where('is_active', true)->findOrFail($agentId);
        if ($ticket->team_id && ! $agent->teams()->whereKey($ticket->team_id)->exists()) {
            throw new \DomainException('The selected agent is not a member of the ticket team.');
        }
        $ticket->forceFill(['assigned_to' => $agent->id])->save();
    }

    private function setPriority(Ticket $ticket, string $priority): void
    {
        if (! in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
            throw new \InvalidArgumentException('Unsupported ticket priority.');
        }
        $ticket->forceFill(['priority' => $priority])->save();
    }

    private function addTag(Ticket $ticket, string $name): void
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 60) {
            throw new \InvalidArgumentException('Tag must contain between 1 and 60 characters.');
        }
        $ticket->tags()->firstOrCreate(['name' => $name], ['created_by' => null]);
    }

    private function notifyAssignee(Ticket $ticket, string $message): void
    {
        $recipient = $ticket->assignedAgent;
        if ($recipient) {
            Notification::send($recipient, new AutomationRuleNotification($ticket, $message));
        }
    }
}
