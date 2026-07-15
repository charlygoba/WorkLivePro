<?php

namespace App\Http\Controllers;

use App\Models\ActivityEvent;
use App\Models\Agent;
use App\Models\Device;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WorkLiveController extends Controller
{
    public function health() { return response()->json(['ok' => true, 'service' => 'work-live-pro', 'companyId' => config('worklive.company_id'), 'date' => now()->toISOString()]); }

    /**
     * Mantiene el contrato del Tracker Windows anterior a Laravel.
     * El cliente compara localmente appPasswordHash; la contraseña nunca viaja en claro.
     */
    public function policy(Request $request)
    {
        $employeeId = trim((string) $request->query('employeeId'));
        if ($employeeId === '') return response()->json(['ok' => false, 'error' => 'employeeId is required'], 400);

        $companyId = config('worklive.company_id');
        $employee = Employee::where('company_id', $companyId)->find($employeeId);
        if (! $employee) return response()->json(['ok' => false, 'error' => 'employeeId no existe en MySQL'], 404);

        $settings = DB::table('company_settings')->where('company_id', $companyId)->first();
        $profile = DB::table('usage_policy_members as member')
            ->join('usage_policy_profiles as profile', function ($join) {
                $join->on('profile.company_id', '=', 'member.company_id')->on('profile.id', '=', 'member.profile_id');
            })
            ->where('member.company_id', $companyId)
            ->where('member.employee_id', $employeeId)
            ->select('profile.*')
            ->first();

        $list = static function ($value): array {
            if (is_array($value)) return array_values($value);
            $decoded = json_decode((string) $value, true);
            return is_array($decoded) ? array_values($decoded) : [];
        };

        $blockedDomains = $profile ? $list($profile->blocked_domains) : $list($settings->unproductive_domains_list ?? null);
        $allowedDomains = $profile ? $list($profile->allowed_domains) : $list($settings->productive_domains_list ?? null);
        $productiveApps = $profile ? $list($profile->productive_apps) : $list($settings->productive_apps_list ?? null);
        $unproductiveApps = $profile ? $list($profile->unproductive_apps) : $list($settings->unproductive_apps_list ?? null);
        $blockedApps = $profile ? $list($profile->blocked_apps) : [];
        $policy = [
            'companyName' => $settings->company_name ?? config('worklive.company_name'),
            'businessHoursStart' => $settings->business_hours_start ?? '08:00',
            'businessHoursEnd' => $settings->business_hours_end ?? '18:00',
            // Nombres heredados que consume el primer Tracker Windows.
            'productiveAppsList' => $productiveApps,
            'unproductiveAppsList' => $unproductiveApps,
            'productiveDomainsList' => $allowedDomains,
            'unproductiveDomainsList' => $blockedDomains,
            'blockedAppsList' => $blockedApps,
            // Nombres del Tracker actual. Se mantienen ambos para no requerir cambios del cliente.
            'blockedDomains' => $blockedDomains,
            'protectedDomains' => $blockedDomains,
            'allowedDomains' => $allowedDomains,
            'productiveApps' => $productiveApps,
            'unproductiveApps' => $unproductiveApps,
            'blockedApps' => $blockedApps,
            'agentSampleIntervalSeconds' => (int) ($settings->agent_sample_interval_seconds ?? 15),
            'agentBatchIntervalSeconds' => (int) ($settings->agent_batch_interval_seconds ?? 300),
            'agentPollIntervalSeconds' => (int) ($settings->agent_poll_interval_seconds ?? 30),
            'blockWebsitesEnabled' => (bool) ($settings->block_websites_enabled ?? false),
            'policyRevision' => (int) ($settings->policy_revision ?? 1),
            'updateFeedUrl' => $settings->update_feed_url ?? '',
            'appPasswordHash' => $settings->app_password_hash ?? null,
            'policyProfile' => $profile ? ['id' => $profile->id, 'name' => $profile->name] : null,
        ];

        // El wrapper settings corresponde al backend anterior; las claves raíz cubren
        // las versiones recientes del Tracker que leen la política directamente.
        return response()->json(['ok' => true, 'settings' => $policy] + $policy);
    }

    public function policyVersion(Request $request)
    {
        $employeeId = trim((string) $request->query('employeeId'));
        if ($employeeId === '') return response()->json(['ok' => false, 'error' => 'employeeId is required'], 400);
        $employee = Employee::where('company_id', config('worklive.company_id'))->find($employeeId);
        if (! $employee) return response()->json(['ok' => false, 'error' => 'employeeId no existe en MySQL'], 404);
        $revision = (int) (DB::table('company_settings')->where('company_id', config('worklive.company_id'))->value('policy_revision') ?? 1);
        return response()->json(['ok' => true, 'policyRevision' => $revision]);
    }

    public function activate(Request $request)
    {
        $data = $request->validate(['clientKey' => ['required', 'string', 'max:64'], 'device' => ['nullable', 'array']]);
        $key = Str::upper(trim($data['clientKey']));
        $employee = Employee::where('company_id', config('worklive.company_id'))->where('client_key', $key)->first();
        if (!$employee) return response()->json(['ok' => false, 'error' => 'No employee found for this clientKey'], 404);
        try {
            $token = Str::random(64);
            [$device, $agent] = DB::transaction(function () use ($employee, $data, $token) {
                $device = $this->upsertDevice($employee, $data['device'] ?? []);
                $agent = Agent::create(['id' => 'agent-'.Str::lower(Str::random(20)), 'company_id' => config('worklive.company_id'), 'employee_id' => $employee->id, 'token_hash' => hash('sha256', $token), 'device_id' => $device->id, 'last_seen_at' => now()]);
                return [$device, $agent];
            });
        } catch (QueryException $exception) {
            Log::error('No se pudo activar el agente WorkLive.', ['employee_id' => $employee->id, 'sql_state' => $exception->getCode(), 'message' => $exception->getMessage()]);
            return response()->json(['ok' => false, 'error' => 'El servidor no pudo guardar la activación. Verifica la conexión MySQL y ejecuta las migraciones pendientes.'], 503);
        }

        return response()->json(['ok' => true, 'agentId' => $agent->id, 'companyId' => config('worklive.company_id'), 'employeeId' => $employee->id, 'employeeName' => $employee->name, 'pollIntervalSeconds' => 60, 'apiToken' => $token]);
    }

    public function event(Request $request) { return response()->json(['ok' => true, ...$this->storeEvent($request->validate($this->eventRules()), $request)]); }

    public function syncRequest(Request $request)
    {
        $agent = $request->attributes->get('agent');
        abort_unless($agent, 403, 'La solicitud solo está disponible para un agente autenticado.');

        $sync = DB::table('agent_sync_requests')
            ->where('company_id', $agent->company_id)
            ->where('employee_id', $agent->employee_id)
            ->where('status', 'requested')
            ->orderBy('requested_at')
            ->first();

        return response()->json([
            'ok' => true,
            'syncRequested' => (bool) $sync,
            'requestId' => $sync?->id,
        ]);
    }

    public function completeSyncRequest(Request $request, string $id)
    {
        $agent = $request->attributes->get('agent');
        abort_unless($agent, 403, 'La solicitud solo está disponible para un agente autenticado.');

        $updated = DB::table('agent_sync_requests')
            ->where('id', $id)
            ->where('company_id', $agent->company_id)
            ->where('employee_id', $agent->employee_id)
            ->where('status', 'requested')
            ->update(['status' => 'completed', 'completed_at' => now()]);

        return response()->json(['ok' => $updated > 0, 'requestId' => $id]);
    }

    public function events(Request $request)
    {
        $data = $request->validate(['events' => ['required', 'array', 'min:1', 'max:500']]);
        $results = [];
        $rows = [];
        $employeeChanges = [];
        $companyId = config('worklive.company_id');
        $agent = $request->attributes->get('agent');

        DB::transaction(function () use ($data, $request, &$results, &$rows, &$employeeChanges, $companyId, $agent) {
            foreach ($data['events'] as $index => $payload) {
                try {
                    $event = validator($payload, $this->eventRules())->validate();
                    $employee = Employee::where('company_id', $companyId)->find($event['employeeId']);
                    if (! $employee) throw new \RuntimeException('employeeId no existe en MySQL');
                    if ($agent && $agent->employee_id !== $employee->id) throw new \RuntimeException('El agente no pertenece al empleado indicado.');

                    if (! empty($event['device']) && is_array($event['device'])) {
                        $device = $this->upsertDevice($employee, $event['device'], $agent?->device_id);
                        if ($agent && $agent->device_id !== $device->id) {
                            $agent->forceFill(['device_id' => $device->id, 'last_seen_at' => now()])->saveQuietly();
                        }
                    }

                    $id = 'evt-'.Str::lower(Str::random(24));
                    $timestamp = Carbon::parse($event['timestamp'] ?? now());
                    $duration = (int) ($event['duration'] ?? 0);
                    $eventType = $event['eventType'];
                    $employeeId = $employee->id;
                    $rows[] = [
                        'id' => $id,
                        'company_id' => $companyId,
                        'employee_id' => $employeeId,
                        'employee_name' => $event['employeeName'] ?? $employee->name,
                        'department' => $event['department'] ?? $employee->department,
                        'event_timestamp' => $timestamp,
                        'event_type' => $eventType,
                        'app' => $event['app'] ?? null,
                        'title' => $event['title'] ?? null,
                        'domain' => $event['domain'] ?? null,
                        'duration' => $duration,
                        'agent_id' => $event['agentId'] ?? ($agent?->id),
                    ];
                    $employeeChanges[$employeeId]['employee'] = $employee;
                    $employeeChanges[$employeeId]['active'] = ($employeeChanges[$employeeId]['active'] ?? 0) + ($eventType === 'active' ? $duration : 0);
                    $employeeChanges[$employeeId]['idle'] = ($employeeChanges[$employeeId]['idle'] ?? 0) + ($eventType === 'idle' ? $duration : 0);
                    $employeeChanges[$employeeId]['last'] = $rows[array_key_last($rows)];
                    $results[] = ['eventId' => $id, 'employeeId' => $employeeId];
                } catch (\Throwable $exception) {
                    Log::warning('Evento omitido durante recepción masiva.', ['index' => $index, 'company_id' => $companyId, 'message' => $exception->getMessage()]);
                    $results[] = ['error' => 'Evento rechazado', 'index' => $index];
                }
            }

            foreach (array_chunk($rows, 250) as $chunk) DB::table('activity_events')->insert($chunk);

            foreach ($employeeChanges as $change) {
                $employee = $change['employee'];
                $last = $change['last'];
                $employee->forceFill([
                    'status' => in_array($last['event_type'], ['active', 'startup'], true) ? 'online' : $last['event_type'],
                    'last_active' => now(),
                    'current_app' => $last['app'] ?? $employee->current_app,
                    'current_title' => $last['title'] ?? $employee->current_title,
                    'current_domain' => $last['domain'] ?? $employee->current_domain,
                    'active_time_today' => $employee->active_time_today + $change['active'],
                    'idle_time_today' => $employee->idle_time_today + $change['idle'],
                ])->saveQuietly();
            }
        });
        return response()->json(['ok' => true, 'count' => count($results), 'results' => $results]);
    }

    public function employees() { return response()->json(['ok' => true, 'employees' => Employee::where('company_id', config('worklive.company_id'))->orderBy('name')->get()->map(fn ($e) => $this->employeeJson($e))]); }

    public function employee(Request $request, string $id) { $employee = Employee::where('company_id', config('worklive.company_id'))->find($id); if (!$employee) return response()->json(['ok' => false, 'error' => 'Empleado no encontrado'], 404); return response()->json(['ok' => true, 'employee' => $this->employeeJson($employee), 'events' => ActivityEvent::where('company_id', config('worklive.company_id'))->where('employee_id', $id)->latest('event_timestamp')->limit((int) $request->query('count', 250))->get()]); }

    public function saveEmployee(Request $request, ?string $id = null)
    {
        $payload = is_array($request->input('employee')) ? $request->input('employee') : $request->all();
        $data = validator($payload, ['id' => ['nullable', 'string', 'max:100'], 'name' => ['required', 'string', 'max:150'], 'department' => ['nullable', 'string', 'max:120'], 'country' => ['nullable', 'string', 'max:120'], 'timezone' => ['nullable', 'string', 'max:80'], 'status' => ['nullable', 'string', 'max:30'], 'clientKey' => ['nullable', 'string', 'max:64']])->validate();
        $employeeId = $id ?: ($data['id'] ?? 'emp-'.Str::lower(Str::random(18))); $key = isset($data['clientKey']) ? Str::upper(trim($data['clientKey'])) : null;
        $employee = Employee::updateOrCreate(['id' => $employeeId, 'company_id' => config('worklive.company_id')], ['company_id' => config('worklive.company_id'), 'name' => $data['name'], 'department' => $data['department'] ?? '', 'country' => $data['country'] ?? '', 'timezone' => $data['timezone'] ?? '', 'status' => $data['status'] ?? 'offline', 'client_key' => $key]);
        return response()->json(['ok' => true, 'companyId' => config('worklive.company_id'), 'employeeId' => $employee->id, 'employee' => $this->employeeJson($employee)]);
    }

    public function deleteEmployee(string $id)
    {
        $employee = Employee::where('company_id', config('worklive.company_id'))->find($id);
        if (!$employee) return response()->json(['ok' => false, 'error' => 'Empleado no encontrado'], 404);
        $employee->delete();
        return response()->json(['ok' => true, 'companyId' => config('worklive.company_id'), 'employeeId' => $id]);
    }

    private function eventRules(): array { return ['employeeId' => ['required', 'string', 'max:100'], 'eventType' => ['required', 'string', 'max:40'], 'employeeName' => ['nullable', 'string', 'max:150'], 'department' => ['nullable', 'string', 'max:120'], 'timestamp' => ['nullable', 'date'], 'app' => ['nullable', 'string', 'max:255'], 'title' => ['nullable', 'string', 'max:1000'], 'domain' => ['nullable', 'string', 'max:255'], 'duration' => ['nullable', 'integer', 'min:0', 'max:86400'], 'device' => ['nullable', 'array'], 'agentId' => ['nullable', 'string', 'max:100']]; }
    private function storeEvent(array $data, Request $request): array
    {
        $employee = Employee::where('company_id', config('worklive.company_id'))->find($data['employeeId']); if (!$employee) abort(404, 'employeeId no existe en MySQL');
        $agent = $request->attributes->get('agent'); if ($agent && $agent->employee_id !== $employee->id) abort(403, 'El agente no pertenece al empleado indicado.');
        if (!empty($data['device']) && is_array($data['device'])) {
            $device = $this->upsertDevice($employee, $data['device'], $agent?->device_id);
            if ($agent && $agent->device_id !== $device->id) {
                $agent->forceFill(['device_id' => $device->id, 'last_seen_at' => now()])->saveQuietly();
            }
        }
        $event = ActivityEvent::create(['id' => 'evt-'.Str::lower(Str::random(24)), 'company_id' => config('worklive.company_id'), 'employee_id' => $employee->id, 'employee_name' => $data['employeeName'] ?? $employee->name, 'department' => $data['department'] ?? $employee->department, 'event_timestamp' => $data['timestamp'] ?? now(), 'event_type' => $data['eventType'], 'app' => $data['app'] ?? null, 'title' => $data['title'] ?? null, 'domain' => $data['domain'] ?? null, 'duration' => $data['duration'] ?? 0, 'agent_id' => $data['agentId'] ?? ($agent?->id)]);
        $employee->forceFill(['status' => in_array($data['eventType'], ['active', 'startup'], true) ? 'online' : $data['eventType'], 'last_active' => now(), 'current_app' => $data['app'] ?? $employee->current_app, 'current_title' => $data['title'] ?? $employee->current_title, 'current_domain' => $data['domain'] ?? $employee->current_domain, 'active_time_today' => $employee->active_time_today + ($data['eventType'] === 'active' ? ($data['duration'] ?? 0) : 0), 'idle_time_today' => $employee->idle_time_today + ($data['eventType'] === 'idle' ? ($data['duration'] ?? 0) : 0)])->saveQuietly();
        return ['eventId' => $event->id, 'employeeId' => $employee->id];
    }

    /**
     * Guarda el perfil completo que ya manda el Tracker y reutiliza su serie
     * como identidad estable. Así una reactivación no duplica el mismo equipo.
     */
    private function upsertDevice(Employee $employee, array $payload, ?string $preferredId = null): Device
    {
        $companyId = config('worklive.company_id');
        $value = static function (array $keys) use ($payload): mixed {
            foreach ($keys as $key) {
                $candidate = $payload[$key] ?? null;
                if ($candidate === null) continue;
                if (is_string($candidate) && (trim($candidate) === '' || strtoupper(trim($candidate)) === 'UNKNOWN')) continue;
                return $candidate;
            }
            return null;
        };

        $serial = $value(['serialNumber', 'serial_number']);
        $hostname = $value(['hostname']);
        $devices = Device::query()
            ->where('company_id', $companyId)
            ->where('employee_id', $employee->id)
            ->when($serial, fn ($query) => $query->where('serial_number', $serial))
            ->get();

        // Si la serie no viene, el agente activo sigue siendo una buena pista,
        // pero no se deduplica por hostname para no fusionar equipos distintos.
        if ($devices->isEmpty() && $preferredId) {
            $devices = Device::query()->where('company_id', $companyId)->where('employee_id', $employee->id)->where('id', $preferredId)->get();
        }

        $score = static fn (Device $device): int => collect(['brand', 'model', 'processor', 'ram', 'storage', 'os', 'version'])
            ->filter(fn ($field) => filled($device->{$field}))->count();
        $device = $devices->sortByDesc($score)->first();
        $current = $device ?: new Device(['id' => 'device-'.Str::lower(Str::random(16))]);
        $pick = static fn (mixed $incoming, mixed $existing = null): mixed => $incoming ?? $existing;

        $current->fill([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'hostname' => $pick($hostname, $current->hostname),
            'os' => $pick($value(['os']), $current->os),
            // Algunos equipos/redes no exponen una IP local al agente. La
            // tabla heredada la define como NOT NULL, por lo que se conserva
            // un valor explícito y visible en vez de abortar la vinculación.
            'ip' => $pick($value(['ip']), $current->ip ?: 'No reportada'),
            'version' => $pick($value(['version']), $current->version),
            'serial_number' => $pick($serial, $current->serial_number),
            'brand' => $pick($value(['brand']), $current->brand),
            'model' => $pick($value(['model']), $current->model),
            'processor' => $pick($value(['processor']), $current->processor),
            'ram' => $pick($value(['ram']), $current->ram),
            'storage' => $pick($value(['storage']), $current->storage),
            'disk_total_gb' => $pick($value(['diskTotalGb', 'disk_total_gb']), $current->disk_total_gb),
            'disk_free_gb' => $pick($value(['diskFreeGb', 'disk_free_gb']), $current->disk_free_gb),
            'disk_used_percent' => $pick($value(['diskUsedPercent', 'disk_used_percent']), $current->disk_used_percent),
            'last_sync' => now(),
        ]);
        $current->save();

        // La misma serie corresponde al mismo equipo. Se conserva el registro
        // más completo y se redirigen los agentes antes de quitar duplicados.
        if ($serial && $devices->count() > 1) {
            $duplicates = $devices->where('id', '!=', $current->id);
            foreach ($duplicates as $duplicate) {
                Agent::where('company_id', $companyId)->where('device_id', $duplicate->id)->update(['device_id' => $current->id]);
                $duplicate->delete();
            }
        }

        return $current;
    }
    private function employeeJson(Employee $e): array { return ['id' => $e->id, 'name' => $e->name, 'department' => $e->department, 'country' => $e->country, 'timezone' => $e->timezone, 'status' => $e->status, 'currentApp' => $e->current_app, 'currentTitle' => $e->current_title, 'currentDomain' => $e->current_domain, 'lastActive' => optional($e->last_active)->toISOString(), 'activeTimeToday' => $e->active_time_today, 'idleTimeToday' => $e->idle_time_today, 'updatedAt' => optional($e->last_active)->toISOString(), 'clientKey' => $e->client_key]; }
}
