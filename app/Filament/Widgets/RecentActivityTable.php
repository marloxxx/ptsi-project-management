<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\Services\AnalyticsServiceInterface;
use App\Models\TicketHistory;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentActivityTable extends BaseWidget
{
    protected static ?string $heading = 'Recent Ticket Activity';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = '60s';

    protected AnalyticsServiceInterface $analyticsService;

    public function boot(AnalyticsServiceInterface $analyticsService): void
    {
        $this->analyticsService = $analyticsService;
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();

        $query = $user
            ? $this->analyticsService->recentActivityQuery($user)
            : TicketHistory::query()->whereRaw('1 = 0');

        return $table
            ->query($query)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('activity_summary')
                    ->label('Activity')
                    ->state(function (TicketHistory $record): string {
                        $ticketName = $record->ticket->name ?? 'Unknown ticket';
                        $trimmed = mb_strlen($ticketName) > 40 ? mb_substr($ticketName, 0, 40) . '…' : $ticketName;
                        $userName = $record->actor->name ?? 'Unknown user';

                        return sprintf('<span class="text-primary-600 font-medium">%s</span> updated "%s"', e($userName), e($trimmed));
                    })
                    ->description(function (TicketHistory $record): string {
                        $timestamp = $record->created_at;
                        $timeLabel = $timestamp->isToday()
                            ? $timestamp->format('H:i')
                            : $timestamp->format('M d, H:i');

                        $project = $record->ticket->project->name ?? 'No Project';
                        $uuid = $record->ticket->uuid ?? 'N/A';

                        return sprintf('%s • %s • %s', $timeLabel, e($uuid), e($project));
                    })
                    ->html()
                    ->searchable(['ticket.name', 'ticket.uuid', 'actor.name'])
                    ->weight('medium'),
                TextColumn::make('toStatus.name')
                    ->label('Status')
                    ->badge()
                    ->alignEnd()
                    ->color(fn(TicketHistory $record): string => $record->toStatus?->is_completed ? 'success' : 'primary'),
            ])
            ->filters([
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('start_date')->label('Start'),
                        DatePicker::make('end_date')->label('End'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['start_date'] ?? null, fn(Builder $inner, string $date): Builder => $inner->whereDate('created_at', '>=', $date))
                            ->when($data['end_date'] ?? null, fn(Builder $inner, string $date): Builder => $inner->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['start_date'] ?? null) {
                            $indicators[] = 'From: ' . Carbon::parse($data['start_date'])->format('M d, Y');
                        }

                        if ($data['end_date'] ?? null) {
                            $indicators[] = 'To: ' . Carbon::parse($data['end_date'])->format('M d, Y');
                        }

                        return $indicators;
                    }),
                Filter::make('today')
                    ->label('Today')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->whereDate('created_at', today())),
                SelectFilter::make('actor')
                    ->relationship('actor', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                Action::make('view')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->tooltip('Open ticket')
                    ->url(fn(TicketHistory $record): ?string => $record->ticket
                        ? route('filament.admin.resources.tickets.view', $record->ticket)
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn(TicketHistory $record): bool => $record->ticket !== null),
            ])
            ->paginated([5, 25, 50])
            ->poll($this->pollingInterval)
            ->striped()
            ->emptyStateHeading('No activity found')
            ->emptyStateDescription('There are no ticket updates for the selected filters.')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
