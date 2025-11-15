<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Tables;

use App\Domain\Services\ProjectServiceInterface;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount(['members', 'ticketStatuses'])
                ->with('members'))
            ->columns([
                TextColumn::make('name')
                    ->label('Project')
                    ->sortable()
                    ->searchable()
                    ->limit(40),

                TextColumn::make('ticket_prefix')
                    ->label('Prefix')
                    ->sortable()
                    ->searchable()
                    ->tooltip(fn (Project $record): string => $record->ticket_prefix),

                TextColumn::make('color')
                    ->label('Color')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?string $state): ?string => $state),

                TextColumn::make('start_date')
                    ->label('Duration')
                    ->date('M d, Y')
                    ->sortable()
                    ->description(function (Project $record): ?string {
                        $startDate = $record->start_date;
                        $endDate = $record->end_date;

                        if (! $startDate instanceof Carbon || ! $endDate instanceof Carbon) {
                            return null;
                        }

                        $formattedEndDate = $endDate->format('M d, Y');
                        $days = $startDate->diffInDays($endDate);

                        return "End: {$formattedEndDate} ({$days} days)";
                    })
                    ->placeholder('-'),

                TextColumn::make('members_count')
                    ->label('Members')
                    ->sortable()
                    ->counts('members')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('ticket_statuses_count')
                    ->label('Statuses')
                    ->sortable()
                    ->counts('ticketStatuses')
                    ->badge()
                    ->color('info'),

                IconColumn::make('pinned_at')
                    ->label('Pinned')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedStar)
                    ->falseIcon(Heroicon::OutlinedStar)
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::OutlinedEye)
                    ->visible(fn (): bool => self::currentUser()?->can('projects.view') ?? false)
                    ->url(fn (Project $record): string => ProjectResource::getUrl('view', ['record' => $record])),
                Action::make('edit')
                    ->label('Edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (): bool => self::currentUser()?->can('projects.update') ?? false)
                    ->url(fn (Project $record): string => ProjectResource::getUrl('edit', ['record' => $record])),
                Action::make('delete')
                    ->label('Delete')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => self::currentUser()?->can('projects.delete') ?? false)
                    ->action(function (Project $record, ProjectServiceInterface $projectService): void {
                        $projectService->delete((int) $record->getKey());
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('bulk-delete')
                    ->label('Delete selected')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => self::currentUser()?->can('projects.delete') ?? false)
                    ->action(function (Collection $records, ProjectServiceInterface $projectService): void {
                        $records->each(
                            fn (Project $project): bool => $projectService->delete((int) $project->getKey())
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
