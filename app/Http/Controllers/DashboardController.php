<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DashboardController extends Controller
{
    public function index()
    {
        $company = config('worklive.company_id');
        $configuredTimezone = DB::table('company_settings')->where('company_id', $company)->value('timezone') ?: 'America/Mexico_City';
        $corporateTimezone = in_array($configuredTimezone, timezone_identifiers_list(), true) ? $configuredTimezone : 'America/Mexico_City';
        $employees = DB::table('employees')->where('company_id', $company)->orderBy('name')->get();
        $employeeTimezones = $employees->mapWithKeys(function ($employee) {
            $timezone = in_array($employee->timezone, timezone_identifiers_list(), true)
                ? $employee->timezone
                : config('app.timezone');

            return [$employee->id => $timezone];
        });

        // Se consultan dos días UTC para cubrir el inicio de jornada de todas las
        // zonas horarias. Después se filtra por "hoy" en la zona de cada empleado.
        $timeEvents = DB::table('activity_events')
            ->where('company_id', $company)
            ->where('event_timestamp', '>=', Carbon::now('UTC')->subDays(2))
            ->get(['employee_id', 'event_timestamp', 'event_type', 'duration']);

        $activeSeconds = 0;
        $idleSeconds = 0;
        $employeeTimes = $employees->mapWithKeys(fn ($employee) => [$employee->id => ['active' => 0, 'idle' => 0]])->all();
        $hourlySeconds = array_fill_keys(range(8, 18), 0);

        foreach ($timeEvents as $event) {
            $timezone = $employeeTimezones->get($event->employee_id);
            if (! $timezone) {
                continue;
            }

            $eventAt = Carbon::parse($event->event_timestamp, 'UTC')->setTimezone($timezone);
            if (! $eventAt->isSameDay(Carbon::now($timezone))) {
                continue;
            }

            $duration = max(0, (int) $event->duration);
            if (! in_array($event->event_type, ['active', 'idle'], true)) {
                continue;
            }

            $employeeTimes[$event->employee_id][$event->event_type] += $duration;
            if ($event->event_type === 'active') {
                $activeSeconds += $duration;
                $hour = (int) $eventAt->format('G');
                if (array_key_exists($hour, $hourlySeconds)) {
                    $hourlySeconds[$hour] += $duration;
                }
            } else {
                $idleSeconds += $duration;
            }
        }

        $stats = ['total' => $employees->count(), 'active' => $employees->whereIn('status', ['online', 'active'])->count(), 'idle' => $employees->where('status', 'idle')->count(), 'locked' => $employees->where('status', 'locked')->count(), 'offline' => $employees->where('status', 'offline')->count(), 'activeSeconds' => $activeSeconds, 'idleSeconds' => $idleSeconds];
        $events = DB::table('activity_events')->where('company_id', $company)->orderByDesc('event_timestamp')->limit(12)->get()
            ->each(fn ($event) => $event->display_timestamp = Carbon::parse($event->event_timestamp, 'UTC')->setTimezone($corporateTimezone));
        $hourly = collect(range(8, 18))->map(fn ($hour) => ['label' => sprintf('%02d:00', $hour), 'value' => $hourlySeconds[$hour], 'sync' => 0]);

        return view('dashboard.index', compact('employees', 'events', 'stats', 'hourly', 'employeeTimes', 'corporateTimezone'));
    }

    public function employees(Request $request)
    {
        $query = DB::table('employees')->where('company_id', config('worklive.company_id'));
        $search = trim((string) $request->input('search', ''));
        $department = trim((string) $request->input('department', 'All'));
        $status = trim((string) $request->input('status', 'All'));

        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('department', 'like', $term)->orWhere('country', 'like', $term)->orWhere('client_key', 'like', $term));
        }
        if ($department !== '' && $department !== 'All') $query->where('department', $department);
        if ($status !== '' && $status !== 'All') $query->where('status', $status);
        $corporateTimezone = $this->corporateTimezone();
        $employees = $query->orderBy('name')->get()->each(function ($employee) use ($corporateTimezone) {
            $employee->last_active_display = $employee->last_active
                ? Carbon::parse($employee->last_active, 'UTC')->setTimezone($corporateTimezone)
                : null;
        });
        $departments = DB::table('employees')->where('company_id', config('worklive.company_id'))->whereNotNull('department')->distinct()->orderBy('department')->pluck('department');
        $clientKeyPrefix = DB::table('company_settings')->where('company_id', config('worklive.company_id'))->value('client_key_prefix') ?: 'SAFEB';
        return view('employees.index', compact('employees','departments','clientKeyPrefix','corporateTimezone'));
    }

    public function storeEmployee(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'department' => ['required', 'string', 'max:255'], 'country' => ['required', 'string', 'max:120'], 'timezone' => ['required', 'string', 'max:120'], 'status' => ['nullable','in:offline,online,active,idle,locked'], 'client_key' => ['nullable', 'string', 'max:120']]);
        $requestedKey = Str::upper(trim((string) ($data['client_key'] ?? '')));
        $configuredPrefix = DB::table('company_settings')->where('company_id', config('worklive.company_id'))->value('client_key_prefix') ?: 'SAFEB';
        $configuredPrefix = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($configuredPrefix)), 0, 12) ?: 'SAFEB';
        $key = $requestedKey === '' || $requestedKey === $configuredPrefix.'-' ? $this->generateClientKey() : $requestedKey;
        DB::table('employees')->insert(['id' => 'emp-'.Str::lower(Str::random(18)), 'company_id' => config('worklive.company_id'), 'name' => $data['name'], 'department' => $data['department'], 'country' => $data['country'], 'timezone' => $data['timezone'], 'status' => $data['status'] ?? 'offline', 'active_time_today' => 0, 'idle_time_today' => 0, 'updated_at' => now(), 'client_key' => $key]);
        return redirect()->route('employees')->with('success', 'Empleado registrado correctamente.');
    }

    public function updateEmployee(Request $request, string $id)
    {
        $data = $request->validate(['name' => ['required','string','max:255'], 'department' => ['required','string','max:255'], 'country' => ['required','string','max:120'], 'timezone' => ['required','string','max:120'], 'status' => ['required','in:offline,online,active,idle,locked'], 'client_key' => ['nullable','string','max:120']]);
        DB::table('employees')->where('company_id', config('worklive.company_id'))->where('id',$id)->update(['name'=>$data['name'],'department'=>$data['department'],'country'=>$data['country'],'timezone'=>$data['timezone'],'status'=>$data['status'],'client_key'=>$data['client_key'] ? Str::upper(trim($data['client_key'])) : null,'updated_at'=>now()]);
        return redirect()->route('employees.show',$id)->with('success','Empleado actualizado correctamente.');
    }

    public function requestEmployeeSync(string $id)
    {
        $company = config('worklive.company_id');
        $employee = DB::table('employees')->where('company_id', $company)->where('id', $id)->first();
        abort_unless($employee, 404);

        $pending = DB::table('agent_sync_requests')
            ->where('company_id', $company)
            ->where('employee_id', $id)
            ->where('status', 'requested')
            ->exists();

        if (! $pending) {
            DB::table('agent_sync_requests')->insert([
                'id' => 'sync-'.Str::lower(Str::random(24)),
                'company_id' => $company,
                'employee_id' => $id,
                'status' => 'requested',
                'requested_by' => session('worklive_admin.email'),
                'requested_at' => now(),
            ]);
        }

        return back()->with('success', 'Solicitud enviada a '.$employee->name.'. El cliente la atenderá en su próxima consulta.');
    }

    private function generateClientKey(): string
    {
        $prefix = DB::table('company_settings')->where('company_id', config('worklive.company_id'))->value('client_key_prefix') ?: 'SAFEB';
        $prefix = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($prefix)), 0, 12) ?: 'SAFEB';
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $blocks = collect(range(1, 4))->map(function () use ($alphabet) {
            return collect(range(1, 4))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->join('');
        })->join('-');
        return $prefix.'-'.$blocks;
    }

    public function deleteWebEmployee(string $id)
    {
        $email = strtolower(trim((string) session('worklive_admin.email')));
        $isAdmin = $email !== '' && DB::table('authorized_admins')->where('company_id', config('worklive.company_id'))->where('email', $email)->exists();
        if (! $isAdmin) return back()->withErrors(['employee'=>'Solo un administrador autorizado puede eliminar empleados.']);
        DB::table('employees')->where('company_id',config('worklive.company_id'))->where('id',$id)->delete();
        return redirect()->route('employees')->with('success','Empleado eliminado correctamente.');
    }

    public function employee(Request $request, string $id)
    {
        $company = config('worklive.company_id');
        $corporateTimezone = $this->corporateTimezone($company);
        $employee = DB::table('employees')->where('company_id', $company)->where('id', $id)->firstOrFail();
        $tab = (string) $request->query('tab', 'overview');
        $eventQuery = DB::table('activity_events')->where('company_id', $company)->where('employee_id', $id);
        if ($request->filled('date_from')) $eventQuery->where('event_timestamp','>=',Carbon::parse($request->string('date_from'), $corporateTimezone)->startOfDay()->utc());
        if ($request->filled('date_to')) $eventQuery->where('event_timestamp','<=',Carbon::parse($request->string('date_to'), $corporateTimezone)->endOfDay()->utc());
        if ($request->filled('app')) $eventQuery->where('app','like','%'.$request->string('app').'%');
        if ($request->filled('domain')) $eventQuery->where('domain','like','%'.$request->string('domain').'%');
        $eventType = (string) $request->query('event_type', 'all');
        if ($eventType !== '' && $eventType !== 'all') $eventQuery->where('event_type', $eventType);
        if ($request->boolean('blocked_only')) $eventQuery->whereIn('event_type',['blocked','blocked-site']);
        $eventSearch = trim((string) $request->query('event_search',''));
        if ($eventSearch !== '') $eventQuery->where(fn ($q) => $q->where('app','like','%'.$eventSearch.'%')->orWhere('title','like','%'.$eventSearch.'%')->orWhere('domain','like','%'.$eventSearch.'%'));
        $eventsPaginator = null;
        if ($tab === 'events') {
            $perPage = (int) $request->query('per_page', 100);
            $perPage = in_array($perPage, [50, 100, 250, 500, 1000], true) ? $perPage : 100;
            $eventsPaginator = $eventQuery->orderByDesc('event_timestamp')->paginate($perPage)->withQueryString();
            $events = $eventsPaginator->getCollection();
        } else {
            $events = $eventQuery->orderByDesc('event_timestamp')->limit(250)->get();
        }
        $events->each(fn ($event) => $event->display_timestamp = Carbon::parse($event->event_timestamp, 'UTC')->setTimezone($corporateTimezone));
        $eventTotal = $eventsPaginator?->total() ?? $events->count();
        $timezone = in_array($employee->timezone, timezone_identifiers_list(), true) ? $employee->timezone : config('app.timezone');
        $nowForEmployee = Carbon::now($timezone);
        $todayStart = $nowForEmployee->copy()->startOfDay();
        $weekStart = $nowForEmployee->copy()->startOfWeek(Carbon::MONDAY);
        $periodEvents = DB::table('activity_events')
            ->where('company_id', $company)
            ->where('employee_id', $id)
            ->whereBetween('event_timestamp', [$weekStart->copy()->utc(), $nowForEmployee->copy()->utc()])
            ->get(['event_timestamp', 'event_type', 'duration']);
        $inToday = fn ($event) => Carbon::parse($event->event_timestamp, 'UTC')->setTimezone($timezone)->greaterThanOrEqualTo($todayStart);
        $sumTime = fn ($type, $onlyToday = false) => (int) $periodEvents
            ->filter(fn ($event) => $event->event_type === $type && (!$onlyToday || $inToday($event)))
            ->sum('duration');
        $timeMetrics = [
            'todayActive' => $sumTime('active', true),
            'todayIdle' => $sumTime('idle', true),
            'weekActive' => $sumTime('active'),
            'weekIdle' => $sumTime('idle'),
            'weekStart' => $weekStart->format('d/m'),
            'today' => $nowForEmployee->format('d/m/Y'),
        ];
        $summaries = DB::table('daily_summaries')->where('company_id',$company)->where('employee_id',$id)->orderByDesc('summary_date')->limit(30)->get();
        $devices = DB::table('devices')->where('company_id', $company)->where('employee_id', $id)->orderByDesc('last_sync')->get();
        $appTotals = $events->groupBy(fn($e) => $e->app ?: 'Sin aplicación')->map(fn($items) => $items->sum('duration'))->sortDesc()->take(8);
        $domainTotals = $events->groupBy(fn($e) => $e->domain ?: 'Sin dominio')->map(fn($items) => $items->sum('duration'))->sortDesc()->take(8);
        return view('employees.show', compact('employee', 'events', 'eventsPaginator', 'eventTotal', 'timeMetrics', 'summaries', 'devices', 'appTotals', 'domainTotals', 'corporateTimezone'));
    }

    public function updateDevice(Request $request, string $employeeId, string $deviceId)
    {
        $data = $request->validate(['brand'=>['nullable','string','max:120'],'model'=>['nullable','string','max:120'],'processor'=>['nullable','string','max:255'],'ram'=>['nullable','string','max:120'],'storage'=>['nullable','string','max:120'],'serial_number'=>['nullable','string','max:255'],'os'=>['required','string','max:255']]);
        DB::table('devices')->where('company_id',config('worklive.company_id'))->where('employee_id',$employeeId)->where('id',$deviceId)->update($data+['last_sync'=>now()]);
        return redirect()->to(route('employees.show',$employeeId).'?tab=device')->with('success','Dispositivo actualizado correctamente.');
    }

    public function reports(Request $request)
    {
        $corporateTimezone = $this->corporateTimezone();
        $company = config('worklive.company_id');
        $from = (string) $request->query('date_from', $request->query('filter_date', now($corporateTimezone)->startOfMonth()->toDateString()));
        $to = (string) $request->query('date_to', $request->query('filter_date', now($corporateTimezone)->toDateString()));
        if ($from > $to) [$from, $to] = [$to, $from];
        $tab = (string) $request->query('tab', 'overview');
        if (!in_array($tab, ['overview', 'attendance', 'productivity', 'incidents'], true)) $tab = 'overview';
        $query = DB::table('daily_summaries')->where('company_id', $company)->whereBetween('summary_date', [$from, $to]);
        if ($request->filled('employee_id') && $request->string('employee_id') !== 'All') $query->where('employee_id', $request->string('employee_id'));
        if ($request->filled('department') && $request->string('department') !== 'All') $query->where('department', $request->string('department'));
        if ($request->filled('country') && $request->string('country') !== 'All') $query->where('country', $request->string('country'));
        $summaries = $query->orderByDesc('summary_date')->orderBy('employee_name')->get();
        $employeeRows = $summaries->groupBy('employee_id')->map(function ($items) {
            $first = $items->first(); $active = (int) $items->sum('total_active_seconds'); $idle = (int) $items->sum('total_idle_seconds'); $locked = (int) $items->sum('total_locked_seconds');
            return (object)['employee_id'=>$first->employee_id,'employee_name'=>$first->employee_name,'department'=>$first->department ?: '—','country'=>$first->country ?: '—','days'=>$items->count(),'active'=>$active,'idle'=>$idle,'locked'=>$locked,'productivity'=>$active + $idle > 0 ? (int) round(($active / ($active + $idle)) * 100) : 0,'first_activity'=>$items->pluck('first_activity')->filter()->min(),'last_activity'=>$items->pluck('last_activity')->filter()->max()];
        })->values();
        $selectedIds = $employeeRows->pluck('employee_id')->filter()->values();
        $incidentQuery = DB::table('activity_events')->where('company_id', $company)->whereBetween('event_timestamp', [Carbon::parse($from, $corporateTimezone)->startOfDay()->utc(), Carbon::parse($to, $corporateTimezone)->endOfDay()->utc()]);
        if ($selectedIds->isNotEmpty()) $incidentQuery->whereIn('employee_id', $selectedIds);
        $incidents = $incidentQuery->whereIn('event_type', ['locked', 'blocked', 'blocked-site'])->orderByDesc('event_timestamp')->limit(250)->get()
            ->each(fn ($event) => $event->display_timestamp = Carbon::parse($event->event_timestamp, 'UTC')->setTimezone($corporateTimezone));
        $metrics = (object)['employees'=>$employeeRows->count(),'active'=>(int)$summaries->sum('total_active_seconds'),'idle'=>(int)$summaries->sum('total_idle_seconds'),'locked'=>(int)$summaries->sum('total_locked_seconds'),'incidents'=>$incidents->count()];
        $metrics->productivity = $metrics->active + $metrics->idle > 0 ? (int) round(($metrics->active / ($metrics->active + $metrics->idle)) * 100) : 0;
        $base = DB::table('daily_summaries')->where('company_id', $company);
        $employees = (clone $base)->select('employee_id','employee_name')->distinct()->orderBy('employee_name')->get();
        $departments = (clone $base)->whereNotNull('department')->distinct()->orderBy('department')->pluck('department');
        $countries = (clone $base)->whereNotNull('country')->distinct()->orderBy('country')->pluck('country');
        return view('reports.index', compact('summaries', 'employeeRows', 'incidents', 'metrics', 'employees', 'departments', 'countries', 'corporateTimezone', 'from', 'to', 'tab'));
    }

    public function exportReports(Request $request)
    {
        $corporateTimezone = $this->corporateTimezone();
        $from = (string) $request->query('date_from', $request->query('filter_date', now($corporateTimezone)->startOfMonth()->toDateString()));
        $to = (string) $request->query('date_to', $request->query('filter_date', now($corporateTimezone)->toDateString()));
        if ($from > $to) [$from, $to] = [$to, $from];
        $query = DB::table('daily_summaries')->where('company_id', config('worklive.company_id'))->whereBetween('summary_date', [$from, $to]);
        if ($request->filled('employee_id') && $request->string('employee_id') !== 'All') $query->where('employee_id', $request->string('employee_id'));
        if ($request->filled('department') && $request->string('department') !== 'All') $query->where('department', $request->string('department'));
        if ($request->filled('country') && $request->string('country') !== 'All') $query->where('country', $request->string('country'));
        $summaries = $query->orderByDesc('summary_date')->orderBy('employee_name')->limit(10000)->get();
        return response()->streamDownload(function () use ($summaries, $corporateTimezone, $from, $to) { $out = fopen('php://output', 'w'); fputcsv($out, ['Periodo exportado','Zona horaria',$from.' a '.$to,$corporateTimezone]); fputcsv($out, []); fputcsv($out, ['Fecha','Empleado','Departamento','País','Horas Activo','Horas Inactivo','Horas Bloqueado','Primera Actividad','Última Actividad']); foreach ($summaries as $summary) fputcsv($out, [$summary->summary_date,$summary->employee_name,$summary->department,$summary->country,number_format($summary->total_active_seconds / 3600, 2),number_format($summary->total_idle_seconds / 3600, 2),number_format($summary->total_locked_seconds / 3600, 2),$summary->first_activity ? Carbon::parse($summary->first_activity, 'UTC')->setTimezone($corporateTimezone)->format('Y-m-d H:i:s') : '—',$summary->last_activity ? Carbon::parse($summary->last_activity, 'UTC')->setTimezone($corporateTimezone)->format('Y-m-d H:i:s') : '—']); fclose($out); }, 'worklive-reporte-'.$from.'_'.$to.'.csv');
    }

    public function exportReportsXlsx(Request $request)
    {
        [$summaries, $from, $to, $corporateTimezone] = $this->reportExportData($request);
        $sheet = (new Spreadsheet())->getActiveSheet();
        $sheet->setTitle('Reporte WorkLive');
        $sheet->mergeCells('A1:I1')->setCellValue('A1', 'WorkLive Pro · Reporte de productividad');
        $sheet->mergeCells('A2:I2')->setCellValue('A2', "Periodo: {$from} a {$to} · Zona horaria: {$corporateTimezone}");
        $sheet->fromArray(['Fecha','Empleado','Departamento','País','Activo','Inactivo','Bloqueado','Inicio','Cierre'], null, 'A4');
        $row = 5;
        foreach ($summaries as $summary) {
            $sheet->fromArray([[$summary->summary_date, $summary->employee_name, $summary->department, $summary->country, round($summary->total_active_seconds / 3600, 2), round($summary->total_idle_seconds / 3600, 2), round($summary->total_locked_seconds / 3600, 2), $summary->first_activity ? Carbon::parse($summary->first_activity, 'UTC')->setTimezone($corporateTimezone)->format('H:i:s') : '—', $summary->last_activity ? Carbon::parse($summary->last_activity, 'UTC')->setTimezone($corporateTimezone)->format('H:i:s') : '—']], null, "A{$row}");
            $row++;
        }
        $sheet->getStyle('A1:I1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('312E81');
        $sheet->getStyle('A4:I4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:I4')->getFill()->setFillType('solid')->getStartColor()->setRGB('4F46E5');
        $sheet->freezePane('A5'); $sheet->setAutoFilter("A4:I".max(4, $row - 1));
        foreach (range('A', 'I') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
        return response()->streamDownload(fn () => (new Xlsx($sheet->getParent()))->save('php://output'), "worklive-reporte-{$from}_{$to}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exportReportsPdf(Request $request)
    {
        [$summaries, $from, $to, $corporateTimezone] = $this->reportExportData($request);
        $dompdf = new Dompdf(['isRemoteEnabled' => false]);
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->loadHtml(view('reports.pdf', compact('summaries', 'from', 'to', 'corporateTimezone'))->render());
        $dompdf->render();
        return response($dompdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => "attachment; filename=worklive-reporte-{$from}_{$to}.pdf"]);
    }

    private function reportExportData(Request $request): array
    {
        $corporateTimezone = $this->corporateTimezone();
        $from = (string) $request->query('date_from', $request->query('filter_date', now($corporateTimezone)->startOfMonth()->toDateString()));
        $to = (string) $request->query('date_to', $request->query('filter_date', now($corporateTimezone)->toDateString()));
        if ($from > $to) [$from, $to] = [$to, $from];
        $query = DB::table('daily_summaries')->where('company_id', config('worklive.company_id'))->whereBetween('summary_date', [$from, $to]);
        if ($request->filled('employee_id') && $request->string('employee_id') !== 'All') $query->where('employee_id', $request->string('employee_id'));
        if ($request->filled('department') && $request->string('department') !== 'All') $query->where('department', $request->string('department'));
        if ($request->filled('country') && $request->string('country') !== 'All') $query->where('country', $request->string('country'));
        return [$query->orderByDesc('summary_date')->orderBy('employee_name')->limit(10000)->get(), $from, $to, $corporateTimezone];
    }

    public function policies()
    {
        $company = config('worklive.company_id');
        $policies = DB::table('usage_policy_profiles')->where('company_id', $company)->orderBy('name')->get();
        $membersByPolicy = DB::table('usage_policy_members')->where('company_id', $company)->get()->groupBy('profile_id');
        $employees = DB::table('employees')->where('company_id', $company)->orderBy('name')->get(['id','name','department']);
        $selectedId = (string) request()->query('policy', '');
        $active = request()->boolean('new') ? null : ($policies->firstWhere('id', $selectedId) ?: $policies->first());
        return view('policies.index', compact('policies', 'membersByPolicy', 'employees', 'active'));
    }

    public function storePolicy(Request $request)
    {
        $data = $request->validate(['name' => ['required','string','max:255'], 'description' => ['nullable','string'], 'blocked_domains' => ['nullable','string'], 'allowed_domains' => ['nullable','string'], 'productive_apps' => ['nullable','string'], 'unproductive_apps' => ['nullable','string'], 'blocked_apps' => ['nullable','string'], 'member_employee_ids'=>['array'],'member_employee_ids.*'=>['string','max:120']]);
        $id = 'policy-'.Str::lower(Str::random(18));
        DB::transaction(function () use ($data, $id) {
            $toJson = fn ($value) => json_encode($this->policyItems($value));
            DB::table('usage_policy_profiles')->insert(['id' => $id, 'company_id' => config('worklive.company_id'), 'name' => $data['name'], 'description' => $data['description'] ?? '', 'blocked_domains' => $toJson($data['blocked_domains'] ?? ''), 'allowed_domains' => $toJson($data['allowed_domains'] ?? ''), 'productive_apps' => $toJson($data['productive_apps'] ?? ''), 'unproductive_apps' => $toJson($data['unproductive_apps'] ?? ''), 'blocked_apps' => $toJson($data['blocked_apps'] ?? ''), 'created_at' => now(), 'updated_at' => now()]);
            $this->syncPolicyMembers($id, $data['member_employee_ids'] ?? []);
        });
        return redirect()->route('policies',['policy'=>$id])->with('success', 'Política guardada y usuarios asignados.');
    }

    public function updatePolicy(Request $request, string $id)
    {
        $data = $request->validate(['name'=>['required','string','max:255'],'description'=>['nullable','string'],'blocked_domains'=>['nullable','string'],'allowed_domains'=>['nullable','string'],'productive_apps'=>['nullable','string'],'unproductive_apps'=>['nullable','string'],'blocked_apps'=>['nullable','string'],'member_employee_ids'=>['array'],'member_employee_ids.*'=>['string']]);
        DB::transaction(function () use ($data, $id) {
            $toJson = fn($value) => json_encode($this->policyItems($value));
            DB::table('usage_policy_profiles')->where('company_id',config('worklive.company_id'))->where('id',$id)->update(['name'=>$data['name'],'description'=>$data['description'] ?? '','blocked_domains'=>$toJson($data['blocked_domains'] ?? ''),'allowed_domains'=>$toJson($data['allowed_domains'] ?? ''),'productive_apps'=>$toJson($data['productive_apps'] ?? ''),'unproductive_apps'=>$toJson($data['unproductive_apps'] ?? ''),'blocked_apps'=>$toJson($data['blocked_apps'] ?? ''),'updated_at'=>now()]);
            $this->syncPolicyMembers($id, $data['member_employee_ids'] ?? []);
        });
        return redirect()->route('policies')->with('success','Política y asignaciones actualizadas.');
    }

    public function deletePolicy(string $id)
    {
        DB::table('usage_policy_members')->where('company_id',config('worklive.company_id'))->where('profile_id',$id)->delete();
        DB::table('usage_policy_profiles')->where('company_id',config('worklive.company_id'))->where('id',$id)->delete();
        return redirect()->route('policies')->with('success','Política eliminada.');
    }

    public function pushPoliciesWeb()
    {
        // El Tracker compara esta revisión contra Date.now() (milisegundos).
        // En segundos parecería una revisión más antigua y no actualizaría.
        $revision = (int) floor(microtime(true) * 1000);
        DB::table('company_settings')->where('company_id', config('worklive.company_id'))->update(['policy_revision'=>$revision,'updated_at'=>now()]);
        return back()->with('success', "Actualización de políticas enviada. Revisión {$revision}.");
    }

    private function policyItems(?string $value): array
    {
        return collect(preg_split('/[,;\n]+/', (string) $value))->map(fn ($item) => trim(preg_replace('/^(https?:\/\/|www\.)/i', '', $item)))->filter()->unique()->sort()->values()->all();
    }

    private function syncPolicyMembers(string $policyId, array $employeeIds): void
    {
        $company = config('worklive.company_id');
        $employeeIds = array_values(array_unique($employeeIds));
        DB::table('usage_policy_members')->where('company_id', $company)->where('profile_id', $policyId)->delete();
        if (!$employeeIds) return;
        // Cada empleado tiene una sola política activa, como en el dashboard anterior.
        DB::table('usage_policy_members')->where('company_id', $company)->whereIn('employee_id', $employeeIds)->delete();
        DB::table('usage_policy_members')->insert(collect($employeeIds)->map(fn ($employeeId) => ['company_id'=>$company,'employee_id'=>$employeeId,'profile_id'=>$policyId,'assigned_at'=>now()])->all());
    }

    public function timeClock(Request $request)
    {
        $company = config('worklive.company_id');
        $corporateTimezone = $this->corporateTimezone($company);
        $from = $request->query('date_from', now($corporateTimezone)->toDateString());
        $to = $request->query('date_to', now($corporateTimezone)->toDateString());
        $employeeId = $request->query('employee_id', 'All');
        $employees = DB::table('employees')->where('company_id',$company)->orderBy('name')->get(['id','name','department']);
        $summaries = DB::table('daily_summaries')->where('company_id',$company)->whereBetween('summary_date',[$from,$to])->when($employeeId !== 'All', fn($q) => $q->where('employee_id',$employeeId))->get();
        $events = DB::table('activity_events')->where('company_id',$company)->whereBetween('event_timestamp',[Carbon::parse($from, $corporateTimezone)->startOfDay()->utc(), Carbon::parse($to, $corporateTimezone)->endOfDay()->utc()])->when($employeeId !== 'All', fn($q) => $q->where('employee_id',$employeeId))->orderBy('event_timestamp')->get();
        $settings = DB::table('company_settings')->where('company_id',$company)->first();
        $summaryMap = $summaries->keyBy(fn($s) => $s->employee_id.'__'.$s->summary_date);
        $eventGroups = $events->groupBy(fn($e) => $e->employee_id.'__'.Carbon::parse($e->event_timestamp, 'UTC')->setTimezone($corporateTimezone)->toDateString());
        $keys = $summaryMap->keys()->merge($eventGroups->keys())->unique();
        $rows = $keys->map(function($key) use ($summaryMap,$eventGroups,$employees,$settings,$corporateTimezone) {
            [$id,$date] = explode('__',$key,2); $summary=$summaryMap->get($key); $group=$eventGroups->get($key,collect()); $employee=$employees->firstWhere('id',$id); $ordered=$group->sortBy('event_timestamp')->values();
            $startup=$ordered->firstWhere('event_type','startup'); $shutdown=$ordered->where('event_type','shutdown')->last(); $first=$summary?->first_activity ?: $ordered->first()?->event_timestamp; $last=$summary?->last_activity ?: $ordered->last()?->event_timestamp; $start=$startup?->event_timestamp ?: $first; $end=$shutdown?->event_timestamp ?: $last;
            $startDisplay = $start ? Carbon::parse($start, 'UTC')->setTimezone($corporateTimezone) : null; $endDisplay = $end ? Carbon::parse($end, 'UTC')->setTimezone($corporateTimezone) : null;
            $late = self::minutesLate($startDisplay?->format('Y-m-d H:i:s'),$date,$settings?->business_hours_start ?: '08:00',(int)($settings?->late_arrival_grace_minutes ?? 10)); $early = self::minutesEarly($endDisplay?->format('Y-m-d H:i:s'),$date,$settings?->business_hours_end ?: '18:00',(int)($settings?->early_departure_grace_minutes ?? 10));
            return (object)['employee_id'=>$id,'date'=>$date,'employee_name'=>$summary?->employee_name ?: $employee?->name ?: $id,'department'=>$summary?->department ?: $employee?->department ?: '—','startup'=>$start,'shutdown'=>$end,'startup_display'=>$startDisplay,'shutdown_display'=>$endDisplay,'startup_source'=>$startup ? 'startup' : ($first ? 'activity' : 'none'),'shutdown_source'=>$shutdown ? 'shutdown' : ($last ? 'activity' : 'none'),'active_seconds'=>$summary?->total_active_seconds ?: $ordered->where('event_type','active')->sum('duration'),'idle_seconds'=>$summary?->total_idle_seconds ?: $ordered->where('event_type','idle')->sum('duration'),'locked_seconds'=>$summary?->total_locked_seconds ?: $ordered->where('event_type','locked')->sum('duration'),'blocked_attempts'=>$ordered->filter(fn($e)=>str_contains(strtolower(($e->event_type.' '.$e->app.' '.$e->title.' '.$e->domain)), 'block'))->count(),'late_minutes'=>$late,'early_minutes'=>$early];
        })->sortByDesc(fn($r)=>$r->date)->values();
        return view('time-clock.index', compact('rows','employees','from','to','employeeId','settings','corporateTimezone'));
    }

    private static function minutesLate(?string $value, string $date, string $time, int $grace): int { if (!$value) return 0; $threshold=strtotime($date.' '.$time.' +'.$grace.' minutes'); return max(0,(int)ceil((strtotime($value)-$threshold)/60)); }
    private static function minutesEarly(?string $value, string $date, string $time, int $grace): int { if (!$value) return 0; $threshold=strtotime($date.' '.$time.' -'.$grace.' minutes'); return max(0,(int)ceil(($threshold-strtotime($value))/60)); }

    private function corporateTimezone(?string $company = null): string
    {
        $timezone = DB::table('company_settings')
            ->where('company_id', $company ?: config('worklive.company_id'))
            ->value('timezone') ?: 'America/Mexico_City';

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'America/Mexico_City';
    }

    public function exportTimeClock(Request $request)
    {
        $corporateTimezone = $this->corporateTimezone();
        $request->merge(['date_from'=>$request->query('date_from', now($corporateTimezone)->toDateString()), 'date_to'=>$request->query('date_to', now($corporateTimezone)->toDateString())]);
        $response = $this->timeClock($request); abort_unless($response instanceof \Illuminate\View\View, 500); $rows = $response->getData()['rows'];
        return response()->streamDownload(function() use($rows) { $out=fopen('php://output','w'); fputcsv($out,['Fecha','Empleado','Departamento','Encendido','Apagado','Activo','Inactivo','Bloqueado','Intentos Bloqueados','Retardo Min','Salida Temprana Min']); foreach($rows as $row) fputcsv($out,[$row->date,$row->employee_name,$row->department,$row->startup_display?->format('H:i:s') ?: '—',$row->shutdown_display?->format('H:i:s') ?: '—',gmdate('H:i:s',(int)$row->active_seconds),gmdate('H:i:s',(int)$row->idle_seconds),gmdate('H:i:s',(int)$row->locked_seconds),$row->blocked_attempts,$row->late_minutes,$row->early_minutes]); fclose($out); }, 'reloj-checador-rh-'.now($corporateTimezone)->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function settings()
    {
        $settings = DB::table('company_settings')->where('company_id', config('worklive.company_id'))->first();
        return view('settings.index', compact('settings'));
    }

    public function administrators()
    {
        $settings = DB::table('company_settings')->where('company_id', config('worklive.company_id'))->first();
        $admins = DB::table('authorized_admins')->where('company_id', config('worklive.company_id'))->orderBy('email')->get();
        return view('settings.administrators', compact('settings', 'admins'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate(['company_name'=>['required','string','max:255'],'timezone'=>['required','timezone'],'business_hours_start'=>['required','date_format:H:i'],'business_hours_end'=>['required','date_format:H:i'],'client_key_prefix'=>['required','string','max:12'],'agent_sample_interval_seconds'=>['required','integer','min:5','max:3600'],'agent_batch_interval_seconds'=>['required','integer','min:30','max:86400'],'agent_poll_interval_seconds'=>['required','integer','min:10','max:3600'],'late_arrival_grace_minutes'=>['required','integer','min:0','max:240'],'early_departure_grace_minutes'=>['required','integer','min:0','max:240'],'productive_apps_list'=>['nullable','string'],'unproductive_apps_list'=>['nullable','string'],'productive_domains_list'=>['nullable','string'],'unproductive_domains_list'=>['nullable','string'],'update_feed_url'=>['nullable','url','max:2000'],'app_password'=>['nullable','string','min:8','max:255']]);
        $values = ['company_name'=>$data['company_name'],'timezone'=>$data['timezone'],'business_hours_start'=>$data['business_hours_start'],'business_hours_end'=>$data['business_hours_end'],'client_key_prefix'=>Str::upper($data['client_key_prefix']),'agent_sample_interval_seconds'=>$data['agent_sample_interval_seconds'],'agent_batch_interval_seconds'=>$data['agent_batch_interval_seconds'],'agent_poll_interval_seconds'=>$data['agent_poll_interval_seconds'],'late_arrival_grace_minutes'=>$data['late_arrival_grace_minutes'],'early_departure_grace_minutes'=>$data['early_departure_grace_minutes'],'productive_apps_list'=>$data['productive_apps_list'] ?? '','unproductive_apps_list'=>$data['unproductive_apps_list'] ?? '','productive_domains_list'=>$data['productive_domains_list'] ?? '','unproductive_domains_list'=>$data['unproductive_domains_list'] ?? '','block_websites_enabled'=>$request->boolean('block_websites_enabled'),'policy_revision'=>(int) floor(microtime(true) * 1000),'update_feed_url'=>$data['update_feed_url'] ?? null,'updated_at'=>now()];
        if (!empty($data['app_password'])) $values['app_password_hash'] = password_hash($data['app_password'], PASSWORD_BCRYPT);
        DB::table('company_settings')->where('company_id', config('worklive.company_id'))->update($values);
        return redirect()->route('settings')->with('success', 'Configuración guardada correctamente.');
    }

    public function addAdmin(Request $request)
    {
        $data = $request->validate(['email'=>['required','email','max:255'],'password'=>['required','string','min:8','max:120'],'role'=>['required','in:super_admin,admin,rh']]);
        $email = strtolower($data['email']);
        $companyId = config('worklive.company_id');
        $existing = DB::table('authorized_admins')->where('company_id', $companyId)->where('email', $email)->first();
        $role = $existing?->is_super_admin ? 'super_admin' : $data['role'];
        DB::table('authorized_admins')->updateOrInsert(['company_id'=>$companyId,'email'=>$email],['added_at'=>$existing?->added_at ?? now(),'added_by'=>$existing?->added_by ?? session('worklive_admin.email','admin'),'password_hash'=>password_hash($data['password'],PASSWORD_BCRYPT),'role'=>$role,'is_super_admin'=>$role === 'super_admin']);
        return redirect()->route('settings.admins.index')->with('success','Administrador guardado correctamente.');
    }

    public function removeAdmin(string $email)
    {
        $email = urldecode($email);
        $admin = DB::table('authorized_admins')->where('company_id', config('worklive.company_id'))->where('email', $email)->first();
        if (!$admin || $admin->is_super_admin || strtolower($email) === strtolower(env('ADMIN_BOOTSTRAP_EMAIL', 'admin@empresa.com'))) return back()->withErrors(['admin'=>'No se puede eliminar el super administrador.']);
        DB::table('authorized_admins')->where('company_id',config('worklive.company_id'))->where('email',$email)->delete(); return redirect()->route('settings.admins.index')->with('success','Administrador eliminado.');
    }
}
