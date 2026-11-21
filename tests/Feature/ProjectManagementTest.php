<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_project_with_agent_and_customer_members(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        Livewire::actingAs($admin)->test('projects.index')->call('create')->set('name', 'Migration Project')->set('description', 'Project description')->set('members', [$agent->id, $customer->id])->call('save')->assertHasNoErrors();
        $p = Project::where('name', 'Migration Project')->firstOrFail();
        $this->assertDatabaseHas('project_user', ['project_id' => $p->id, 'user_id' => $agent->id]);
        $this->assertDatabaseHas('project_user', ['project_id' => $p->id, 'user_id' => $customer->id]);
    }

    public function test_only_members_can_view_project(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $member = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $outsider = User::factory()->create();
        $outsider->assignRole('customer');
        $p = Project::create(['name' => 'Private Project', 'created_by' => $admin->id]);
        $p->members()->attach($member);
        $this->assertTrue(Gate::forUser($member)->allows('view', $p));
        $this->assertFalse(Gate::forUser($outsider)->allows('view', $p));
    }

    public function test_deleting_project_detaches_but_keeps_tickets(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $p = Project::create(['name' => 'Keep Ticket', 'created_by' => $admin->id]);
        $t = Ticket::create(['subject' => 'Keep me', 'description' => 'Ticket description is long enough.', 'status' => 'open', 'priority' => 'normal', 'user_id' => $customer->id, 'project_id' => $p->id]);
        Livewire::actingAs($admin)->test('projects.index')->call('delete', $p->id);
        $this->assertDatabaseHas('tickets', ['id' => $t->id, 'project_id' => null]);
    }

    public function test_project_member_can_upload_and_download_private_document(): void
    {
        Storage::fake('local');
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $member = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $p = Project::create(['name' => 'Documents Project', 'created_by' => $admin->id]);
        $p->members()->attach($member);
        Livewire::actingAs($member)->test('projects.index')->call('show', $p->id)
            ->set('document', UploadedFile::fake()->createWithContent(
                'requirements.pdf',
                "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"
            ))
            ->call('uploadDocument')->assertHasNoErrors();
        $doc = $p->documents()->firstOrFail();
        Storage::disk('local')->assertExists($doc->path);
        $this->actingAs($member)->get(route('projects.documents.download', $doc))->assertOk();
        $outsider = User::factory()->create();
        $outsider->assignRole('customer');
        $this->actingAs($outsider)->get(route('projects.documents.download', $doc))->assertForbidden();
    }

    public function test_admin_can_save_project_budget_dates_and_status(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        Livewire::actingAs($admin)->test('projects.index')
            ->call('create')
            ->set('name', 'Budgeted Project')
            ->set('description', 'Project with a defined budget and delivery period.')
            ->set('budget', '15000.50')
            ->set('startDate', '2026-11-01')
            ->set('endDate', '2027-02-28')
            ->set('status', 'active')
            ->call('save')
            ->assertHasNoErrors();
        $project = Project::where('name', 'Budgeted Project')->firstOrFail();
        $this->assertSame('15000.50', $project->budget);
        $this->assertSame('2026-11-01', $project->start_date->format('Y-m-d'));
        $this->assertSame('2027-02-28', $project->end_date->format('Y-m-d'));
        $this->assertSame('active', $project->status);
    }
}
