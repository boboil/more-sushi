<?php

namespace Tests\Feature;

use App\Filament\Pages\Salary;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Role;
use App\Models\SalaryDay;
use App\Models\User;
use App\Models\WorkingHours;
use App\Services\Salary\SalaryPool;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class SalaryPoolTest extends TestCase
{
    use RefreshDatabase;

    private const DAY = '2026-09-01';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** 1 вересня з таблиці «Розрахунок_вересень»: каса 115 563, 8 людей на зміні. */
    public function test_day_matches_spreadsheet(): void
    {
        $sushi = $this->role('sushi', 'Сушист', 1.0);
        $admin = $this->role('shift_admin', 'Адміністратор', 1.0);
        $pizzaHelper = $this->role('pizza_helper', 'Помічник піццайоло', 0.85);
        $packer = $this->role('packer', 'Упаковщик', 0.7);

        $pool = app(SalaryPool::class);
        $date = CarbonImmutable::parse(self::DAY);
        $pool->setCash($date, 115563, null);

        $people = [
            ['Наконечний Д', $sushi, 12], ['Мірошніченко А', $sushi, 12], ['Борисенко А', $sushi, 5],
            ['Бабенко С', $sushi, 12], ['Ярмакович М', $admin, 12], ['Молотков О', $pizzaHelper, 12],
            ['Бессараб Ю', $packer, 12], ['Балак Вадим', $sushi, 12],
        ];
        $ids = [];
        foreach ($people as [$name, $role, $hours]) {
            $user = $this->employee($name, $role);
            $ids[$name] = $user->id;
            $pool->setHours($user->id, $date, $hours, WorkingHours::SOURCE_ADMIN, null);
        }
        // у штаті, але не працював
        $this->employee('Одарченко К', $admin);

        $report = $pool->month($date);
        $day = $report['days'][self::DAY];
        $byName = collect($report['employees'])->keyBy('name');

        $this->assertEqualsWithDelta(17334.45, $day['pool'], 0.001);
        $this->assertEqualsWithDelta(83.6, $day['weight'], 0.0001);
        $this->assertEqualsWithDelta(2488.20, $byName['Наконечний Д']['pay'], 0.01);
        $this->assertEqualsWithDelta(1036.75, $byName['Борисенко А']['pay'], 0.01);
        $this->assertEqualsWithDelta(2114.97, $byName['Молотков О']['pay'], 0.01);
        $this->assertEqualsWithDelta(1741.74, $byName['Бессараб Ю']['pay'], 0.01);
        $this->assertEqualsWithDelta(207.35, $byName['Наконечний Д']['perHour'], 0.01);
        $this->assertSame(0.0, $byName['Одарченко К']['pay']);
        $this->assertEqualsWithDelta(17334.45, $report['totals']['paid'], 0.01, 'весь фонд розподілено');
        $this->assertCount(9, $report['employees']);
    }

    public function test_cash_without_hours_is_flagged_and_percent_is_snapshotted(): void
    {
        $pool = app(SalaryPool::class);
        $pool->setCash(CarbonImmutable::parse('2026-09-02'), 10000, null);
        $pool->setPercent(20);
        $pool->setCash(CarbonImmutable::parse('2026-09-03'), 10000, null);

        $report = $pool->month(CarbonImmutable::parse(self::DAY));

        $this->assertTrue($report['days']['2026-09-02']['unallocated']);
        $this->assertEqualsWithDelta(1500, $report['days']['2026-09-02']['pool'], 0.001);
        $this->assertEqualsWithDelta(2000, $report['days']['2026-09-03']['pool'], 0.001);

        $pool->applyPercentToMonth(CarbonImmutable::parse(self::DAY), 20);
        $this->assertEqualsWithDelta(2000, $pool->month(CarbonImmutable::parse(self::DAY))['days']['2026-09-02']['pool'], 0.001);
    }

    public function test_employee_sees_and_edits_only_own_hours(): void
    {
        Carbon::setTestNow('2026-09-10 20:00:00');
        $sushi = $this->role('sushi', 'Сушист', 1.0);
        $me = $this->employee('Я', $sushi);
        $colleague = $this->employee('Колега', $sushi);
        WorkingHours::query()->create(['user_id' => $colleague->id, 'working_day' => '2026-09-10', 'hours' => 9, 'source' => 'self']);

        $this->getJson('/staff/hours')->assertUnauthorized();

        $this->actingAs($me)->withoutMiddleware(VerifyCsrfToken::class);
        $this->postJson('/staff/hours', ['date' => '2026-09-10', 'hours' => 11.5])
            ->assertOk()
            ->assertJsonPath('totalHours', 11.5)
            ->assertJsonPath('days.9.hours', 11.5)
            ->assertJsonPath('days.9.source', 'self');

        $response = $this->getJson('/staff/hours?month=2026-09')->assertOk();
        $this->assertSame(11.5, $response->json('totalHours'), 'години колеги не видно');
        $this->assertStringNotContainsString('Колега', $response->getContent());

        // не наперед і не давніше тижня
        $this->postJson('/staff/hours', ['date' => '2026-09-11', 'hours' => 8])->assertStatus(422);
        $this->postJson('/staff/hours', ['date' => '2026-09-02', 'hours' => 8])->assertStatus(422);
        $this->postJson('/staff/hours', ['date' => '2026-09-10', 'hours' => 30])->assertStatus(422);

        // години від адміністратора працівник не переписує
        app(SalaryPool::class)->setHours($me->id, CarbonImmutable::parse('2026-09-09'), 10, WorkingHours::SOURCE_ADMIN, null);
        $this->postJson('/staff/hours', ['date' => '2026-09-09', 'hours' => 12])->assertStatus(422);

        // 0 — видалити свій запис
        $this->postJson('/staff/hours', ['date' => '2026-09-10', 'hours' => 0])->assertOk()->assertJsonPath('days.9.hours', null);
        $this->assertSame(9.0, WorkingHours::query()->where('user_id', $colleague->id)->value('hours'));
    }

    public function test_admin_page_saves_day_and_keeps_self_marks(): void
    {
        Carbon::setTestNow('2026-09-05 12:00:00');
        $sushi = $this->role('sushi', 'Сушист', 1.0);
        $packer = $this->role('packer', 'Упаковщик', 0.7);
        $a = $this->employee('Анна', $sushi);
        $b = $this->employee('Богдан', $packer);
        WorkingHours::query()->create(['user_id' => $a->id, 'working_day' => '2026-09-05', 'hours' => 12, 'source' => 'self', 'entered_by' => $a->id]);

        $owner = $this->employee('Власник', $this->role('admin', 'Адміністратор', null));
        $this->actingAs($owner)->get('/admin/salary')->assertOk()->assertSee('Анна')->assertSee('Богдан');

        $page = Livewire::test(Salary::class)->set('month', '2026-09')->mountAction('editDay', ['date' => '2026-09-05']);
        $page->assertSet('mountedActions.0.data.hours.' . $a->id, 12.0, 'години працівника підставлені у форму');
        $this->callMounted($page, ['cash' => 20000, 'hours.' . $b->id => 10]);

        $this->assertEqualsWithDelta(20000, (float) SalaryDay::query()->value('cash'), 0.001);
        $this->assertSame('self', WorkingHours::query()->where('user_id', $a->id)->value('source'), 'не змінені години лишаються «від працівника»');
        $this->assertSame('admin', WorkingHours::query()->where('user_id', $b->id)->value('source'));

        $report = app(SalaryPool::class)->month(CarbonImmutable::parse('2026-09-01'));
        // фонд 3000: Анна 12 × 1 = 12, Богдан 10 × 0.7 = 7
        $this->assertEqualsWithDelta(3000 * 12 / 19, collect($report['employees'])->firstWhere('name', 'Анна')['pay'], 0.01);
    }

    public function test_settings_and_access(): void
    {
        $role = $this->role('sushi', 'Сушист', null);
        $owner = $this->employee('Власник', $this->role('admin', 'Адміністратор', null));
        $manager = $this->employee('Менеджер', $this->role('manager', 'Менеджер', null));

        $this->actingAs($manager)->get('/admin/salary')->assertForbidden();
        $this->actingAs($manager)->get('/admin/rates')->assertNotFound();

        $this->actingAs($owner);
        $page = Livewire::test(Salary::class)->mountAction('settings');
        $this->callMounted($page, ['percent' => 12, 'apply_to_month' => false, 'roles.' . $role->id => 0.8]);

        $this->assertSame(12.0, app(SalaryPool::class)->percent());
        $this->assertSame('0.80', $role->fresh()->salary_coefficient);
    }

    /** Заповнити поля відкритої модалки і натиснути «Зберегти». */
    private function callMounted($page, array $data): void
    {
        foreach ($data as $key => $value) {
            $page->set('mountedActions.0.data.' . $key, $value);
        }
        $page->callMountedAction()->assertHasNoActionErrors()->assertSet('mountedActions', []);
    }

    private function role(string $name, string $label, ?float $coefficient): Role
    {
        return Role::query()->create(['name' => $name, 'label' => $label, 'salary_coefficient' => $coefficient]);
    }

    private function employee(string $name, Role $role): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->roles()->attach($role);

        return $user->load('roles');
    }
}
