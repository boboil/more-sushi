<?php

namespace App\Filament\Pages;

use App\Models\Role;
use App\Models\WorkingHours;
use App\Services\Salary\SalaryPool;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Календар зарплати: каса по днях, години працівників і розрахунок виплат з фонду.
 */
class Salary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Зарплата';

    protected static string|UnitEnum|null $navigationGroup = 'Персонал';

    protected static ?string $title = 'Зарплата';

    protected static ?string $slug = 'salary';

    protected string $view = 'filament.pages.salary';

    #[Url]
    public string $month = '';

    /** hours — у клітинках години, money — гривні */
    #[Url]
    public string $mode = 'hours';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = now()->format('Y-m');
        }
        if (! in_array($this->mode, ['hours', 'money'], true)) {
            $this->mode = 'hours';
        }
    }

    public function monthStart(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m', $this->month);
    }

    public function shiftMonth(int $delta): void
    {
        $this->month = $this->monthStart()->addMonths($delta)->format('Y-m');
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode === 'money' ? 'money' : 'hours';
    }

    protected function getViewData(): array
    {
        $start = $this->monthStart();

        return [
            'report' => app(SalaryPool::class)->month($start),
            'monthLabel' => mb_convert_case($start->locale('uk')->translatedFormat('F Y'), MB_CASE_TITLE),
            'today' => now()->toDateString(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [$this->settingsAction()];
    }

    public function settingsAction(): Action
    {
        return Action::make('settings')
            ->label('Відсоток і коефіцієнти')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->color('gray')
            ->modalHeading('Налаштування розрахунку')
            ->modalSubmitActionLabel('Зберегти')
            ->fillForm(function (): array {
                $pool = app(SalaryPool::class);

                return [
                    'percent' => $pool->percent(),
                    'apply_to_month' => true,
                    'roles' => $pool->roles()->mapWithKeys(fn (Role $r) => [$r->id => $r->salary_coefficient])->all(),
                ];
            })
            ->schema(fn (): array => [
                TextInput::make('percent')
                    ->label('Відсоток від каси на зарплату')
                    ->suffix('%')
                    ->numeric()->minValue(0)->maxValue(100)->step(0.1)->required(),
                Toggle::make('apply_to_month')
                    ->label('Перерахувати вже внесені каси цього місяця за новим відсотком')
                    ->helperText('Інакше новий відсоток діятиме лише для кас, які внесуть далі.'),
                Section::make('Коефіцієнти посад')
                    ->description('Порожньо — посада не бере участі в розподілі (наприклад, роль доступу «менеджер»). Працівнику береться найбільший коефіцієнт з його ролей.')
                    ->schema(app(SalaryPool::class)->roles()->map(
                        fn (Role $role) => TextInput::make("roles.{$role->id}")
                            ->label($role->label ?: $role->name)
                            ->numeric()->minValue(0)->maxValue(5)->step(0.05)
                    )->all())
                    ->columns(2),
            ])
            ->action(function (array $data): void {
                $pool = app(SalaryPool::class);
                $percent = (float) $data['percent'];
                $pool->setPercent($percent);
                if ($data['apply_to_month'] ?? false) {
                    $pool->applyPercentToMonth($this->monthStart(), $percent);
                }
                foreach ($data['roles'] ?? [] as $roleId => $coefficient) {
                    Role::query()->whereKey($roleId)->update(['salary_coefficient' => filled($coefficient) ? (float) $coefficient : null]);
                }

                Notification::make()->success()->title('Налаштування збережено')->send();
            });
    }

    public function editDayAction(): Action
    {
        return Action::make('editDay')
            ->modalHeading(fn (array $arguments) => 'День ' . CarbonImmutable::parse($arguments['date'])->locale('uk')->translatedFormat('j F, l'))
            ->modalSubmitActionLabel('Зберегти')
            ->modalWidth('lg')
            ->fillForm(function (array $arguments): array {
                $date = $arguments['date'];
                $report = app(SalaryPool::class)->month(CarbonImmutable::parse($date));
                $hours = [];
                foreach ($report['employees'] as $employee) {
                    $hours[$employee['id']] = $employee['cells'][$date]['hours'] ?? null;
                }

                return ['cash' => $report['days'][$date]['cash'], 'hours' => $hours];
            })
            ->schema(function (array $arguments): array {
                $date = $arguments['date'];
                $report = app(SalaryPool::class)->month(CarbonImmutable::parse($date));
                $day = $report['days'][$date];

                $fields = array_map(function (array $employee) use ($date) {
                    $source = $employee['cells'][$date]['source'] ?? null;
                    $hint = match ($source) {
                        WorkingHours::SOURCE_SELF => 'внесено працівником',
                        WorkingHours::SOURCE_POSTER => 'з Poster',
                        default => null,
                    };

                    return TextInput::make("hours.{$employee['id']}")
                        ->label($employee['name'])
                        ->helperText(trim(($employee['role'] ? $employee['role'] . ' · ' : '') . 'коеф. ' . rtrim(rtrim(number_format($employee['coefficient'], 2, '.', ''), '0'), '.') . ($hint ? ' · ' . $hint : '')))
                        ->suffix('год')
                        ->numeric()->minValue(0)->maxValue(SalaryPool::MAX_HOURS)->step(0.5);
                }, $report['employees']);

                return [
                    TextInput::make('cash')
                        ->label('Каса за день')
                        ->helperText($day['percent'] !== null ? "У фонд іде {$day['percent']}%" : 'У фонд піде ' . app(SalaryPool::class)->percent() . '%')
                        ->suffix('грн')
                        ->numeric()->minValue(0),
                    Section::make('Години')
                        ->description('Порожньо або 0 — не працював.')
                        ->schema($fields)
                        ->columns(2),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $pool = app(SalaryPool::class);
                $date = CarbonImmutable::parse($arguments['date']);
                $adminId = auth()->id();
                $before = collect($pool->month($date)['employees'])
                    ->mapWithKeys(fn (array $e) => [$e['id'] => $e['cells'][$date->toDateString()]['hours'] ?? null]);

                $pool->setCash($date, filled($data['cash'] ?? null) ? (float) $data['cash'] : null, $adminId);

                foreach ($data['hours'] ?? [] as $userId => $hours) {
                    $hours = filled($hours) ? (float) $hours : null;
                    $old = $before[$userId] ?? null;
                    // не змінене значення лишаємо як є, щоб не губити позначку «внесено працівником»
                    if ((float) $old === (float) $hours) {
                        continue;
                    }
                    $pool->setHours((int) $userId, $date, $hours, WorkingHours::SOURCE_ADMIN, $adminId);
                }

                Notification::make()->success()->title('День збережено')->send();
            });
    }
}
