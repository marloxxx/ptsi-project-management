<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Resources\Tickets\RelationManagers\TicketCommentsRelationManager;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketCommentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        Filament::setCurrentPanel('admin');
        $this->seed(RbacSeeder::class);
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Clear permission cache to ensure fresh permissions
        $user->load('roles.permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($user);

        return $user;
    }

    public function test_admin_can_create_ticket_comment_via_relation_manager(): void
    {
        $admin = $this->actingAsAdmin();

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        $priority = TicketPriority::factory()->create();

        $ticket = Ticket::factory()
            ->for($project)
            ->for($status, 'status')
            ->for($priority, 'priority')
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        // Get fresh ticket instance with project and members loaded
        /** @var Ticket $ticket */
        $ticket = Ticket::with('project.members')->findOrFail($ticket->getKey());

        // Refresh admin to ensure permissions are loaded
        $admin->refresh();
        $admin->load('roles.permissions');

        Livewire::test(TicketCommentsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertActionExists(TestAction::make(CreateAction::class)->table())
            ->callAction(
                TestAction::make(CreateAction::class)->table(),
                [
                    'body' => 'This is a test comment',
                    'is_internal' => false,
                ],
            )
            ->assertNotified();

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->getKey(),
            'body' => 'This is a test comment',
            'user_id' => $admin->getKey(),
            'is_internal' => false,
        ]);
    }

    public function test_admin_can_update_ticket_comment(): void
    {
        $admin = $this->actingAsAdmin();

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        $priority = TicketPriority::factory()->create();

        $ticket = Ticket::factory()
            ->for($project)
            ->for($status, 'status')
            ->for($priority, 'priority')
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        $comment = TicketComment::factory()
            ->for($ticket)
            ->create([
                'user_id' => $admin->getKey(),
            ]);

        // Get fresh ticket instance with project and members loaded
        /** @var Ticket $ticket */
        $ticket = Ticket::with('project.members')->findOrFail($ticket->getKey());

        // Get fresh comment instance with ticket and project loaded
        /** @var TicketComment $comment */
        $comment = TicketComment::with('ticket.project.members')->findOrFail($comment->getKey());

        // Refresh admin to ensure permissions are loaded
        $admin->refresh();
        $admin->load('roles.permissions');

        Livewire::test(TicketCommentsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertActionExists(TestAction::make('edit')->table($comment))
            ->callAction(
                TestAction::make('edit')->table($comment),
                data: [
                    'body' => 'Updated comment body',
                    'is_internal' => true,
                ],
            )
            ->assertNotified();

        $comment->refresh();

        $this->assertSame('Updated comment body', $comment->body);
        $this->assertTrue($comment->is_internal);
    }

    public function test_admin_can_delete_ticket_comment(): void
    {
        $admin = $this->actingAsAdmin();

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        $priority = TicketPriority::factory()->create();

        $ticket = Ticket::factory()
            ->for($project)
            ->for($status, 'status')
            ->for($priority, 'priority')
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        $comment = TicketComment::factory()
            ->for($ticket)
            ->create([
                'user_id' => $admin->getKey(),
            ]);

        // Get fresh ticket instance with project and members loaded
        /** @var Ticket $ticket */
        $ticket = Ticket::with('project.members')->findOrFail($ticket->getKey());

        // Get fresh comment instance with ticket and project loaded
        /** @var TicketComment $comment */
        $comment = TicketComment::with('ticket.project.members')->findOrFail($comment->getKey());

        // Refresh admin to ensure permissions are loaded
        $admin->refresh();
        $admin->load('roles.permissions');

        Livewire::test(TicketCommentsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertActionExists(TestAction::make('delete')->table($comment))
            ->callAction(
                TestAction::make('delete')->table($comment),
            )
            ->assertNotified();

        $this->assertDatabaseMissing('ticket_comments', [
            'id' => $comment->getKey(),
        ]);
    }

    public function test_non_project_member_cannot_create_ticket_comment(): void
    {
        $admin = $this->actingAsAdmin();
        $nonMember = User::factory()->create();
        $nonMember->assignRole('admin');

        // Clear permission cache
        $nonMember->load('roles.permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($nonMember);

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        $priority = TicketPriority::factory()->create();

        $ticket = Ticket::factory()
            ->for($project)
            ->for($status, 'status')
            ->for($priority, 'priority')
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        // Get fresh ticket instance with project and members loaded
        /** @var Ticket $ticket */
        $ticket = Ticket::with('project.members')->findOrFail($ticket->getKey());

        Livewire::test(TicketCommentsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertActionHidden(TestAction::make(CreateAction::class)->table());
    }

    public function test_non_project_member_cannot_edit_ticket_comment(): void
    {
        $admin = $this->actingAsAdmin();
        $nonMember = User::factory()->create();
        $nonMember->assignRole('admin');

        // Clear permission cache
        $nonMember->load('roles.permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($nonMember);

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        $priority = TicketPriority::factory()->create();

        $ticket = Ticket::factory()
            ->for($project)
            ->for($status, 'status')
            ->for($priority, 'priority')
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        $comment = TicketComment::factory()
            ->for($ticket)
            ->create([
                'user_id' => $admin->getKey(),
            ]);

        // Get fresh ticket instance with project and members loaded
        /** @var Ticket $ticket */
        $ticket = Ticket::with('project.members')->findOrFail($ticket->getKey());

        // Get fresh comment instance with ticket and project loaded
        /** @var TicketComment $comment */
        $comment = TicketComment::with('ticket.project.members')->findOrFail($comment->getKey());

        Livewire::test(TicketCommentsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertActionHidden(TestAction::make('edit')->table($comment));
    }

    public function test_non_project_member_cannot_delete_ticket_comment(): void
    {
        $admin = $this->actingAsAdmin();
        $nonMember = User::factory()->create();
        $nonMember->assignRole('admin');

        // Clear permission cache
        $nonMember->load('roles.permissions');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($nonMember);

        $project = Project::factory()->create();
        $project->members()->attach($admin);

        $status = TicketStatus::factory()
            ->for($project)
            ->create();

        $priority = TicketPriority::factory()->create();

        $ticket = Ticket::factory()
            ->for($project)
            ->for($status, 'status')
            ->for($priority, 'priority')
            ->create([
                'created_by' => $admin->getKey(),
            ]);

        $comment = TicketComment::factory()
            ->for($ticket)
            ->create([
                'user_id' => $admin->getKey(),
            ]);

        // Get fresh ticket instance with project and members loaded
        /** @var Ticket $ticket */
        $ticket = Ticket::with('project.members')->findOrFail($ticket->getKey());

        // Get fresh comment instance with ticket and project loaded
        /** @var TicketComment $comment */
        $comment = TicketComment::with('ticket.project.members')->findOrFail($comment->getKey());

        Livewire::test(TicketCommentsRelationManager::class, [
            'ownerRecord' => $ticket,
            'pageClass' => ViewTicket::class,
        ])
            ->assertActionHidden(TestAction::make('delete')->table($comment));
    }
}
