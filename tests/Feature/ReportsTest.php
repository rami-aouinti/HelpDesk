<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_view_reports_and_download_csv(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customerId = User::where('email', 'customer@helpdesk.test')->value('id');
        Ticket::create(['subject'=>'Analytics sample','description'=>'Sample','status'=>'resolved','priority'=>'urgent','user_id'=>$customerId]);

        $this->actingAs($admin)->get(route('reports.index'))->assertOk()->assertSee('Reports &amp; Analytics');
        $this->actingAs($admin)->get(route('reports.csv'))->assertOk()->assertHeader('content-type','text/csv; charset=UTF-8');
    }

    public function test_agent_without_report_permission_is_forbidden(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $this->actingAs($agent)->get(route('reports.index'))->assertForbidden();
    }

    public function test_team_filter_is_applied_to_report_metrics(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customerId = User::where('email', 'customer@helpdesk.test')->value('id');
        $team = Team::create(['name'=>'Reporting team']);
        Ticket::create(['subject'=>'Included report ticket','description'=>'x','status'=>'open','priority'=>'normal','user_id'=>$customerId,'team_id'=>$team->id]);
        Ticket::create(['subject'=>'Excluded report ticket','description'=>'x','status'=>'open','priority'=>'normal','user_id'=>$customerId]);

        $this->actingAs($admin)->get(route('reports.index',['report'=>'tickets','team_id'=>$team->id]))
            ->assertOk()->assertSee('Included report ticket')->assertDontSee('Excluded report ticket');
    }
}
