<?php

namespace App\Http\Controllers;

use App\Jobs\ImportAndIndexConsumers;
use App\Models\ImportLogSubdivision;
use Illuminate\Http\Request;
use App\Models\Consumer;
use App\Models\ConsumerHistory;
use App\Models\ImportLog;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

class ConsumerController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()->build();
    }

    public function dashboard(){
        $logs = ImportLog::with('subdivisions')->latest()->get();
        return view('dashboard', compact('logs'));
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials'])->onlyInput('email');
    }
    
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function index(Request $request)
    {
        $perPage = 10;
        $columns = ['id', 'name', 'contactno', 'reference_no', 'occupant_nicno'];

        // Fast & optimized: queries only consumers table, ordered by primary key, no slow COUNT(*) per page
        $consumers = Consumer::select($columns)
            ->orderBy('id', 'asc')
            ->simplePaginate($perPage)
            ->withQueryString();

        // Total count cached (refreshed periodically, no slow COUNT(*) per request on 40M rows)
        $totalConsumers = Cache::remember('consumers_total_count', 3600, function () {
            try {
                return (int) Consumer::count();
            } catch (\Throwable $e) {
                Log::warning("Error counting consumers: " . $e->getMessage());
                return 0;
            }
        });

        return view('consumers.index', compact('consumers', 'totalConsumers'));
    }
    public function searchPage(Request $request)
    {   
        $searchResults = [];
        $total = 0;
        $totalPages = 0;
        $page = $request->get('page', 1);
        if (collect(['name', 'contactno', 'reference_no', 'occupant_nicno'])->some(fn($field) => $request->filled($field))) {
            list($searchResults, $total, $totalPages) = $this->search($request, $page);
            return view('consumers.search', compact( 'searchResults', 'total', 'totalPages', 'page'));
        }   
        return view('consumers.search');
    }
    
    private function search(Request $request, $page)
    {
        $client = ClientBuilder::create()->build();
    
        $perPage = 10;
    
        $params = [
            'index' => 'consumers',
            'body'  => [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'must' => [],
                        'filter' => []
                    ]
                ]
            ]
        ];
    
        if ($request->filled('reference_no')) {
            $params['body']['query']['bool']['filter'][] = ['term' => ['reference_no' => $request->get('reference_no')]];
        }
        if ($request->filled('occupant_nicno')) {
            $params['body']['query']['bool']['must'][] = ['term' => ['occupant_nicno' => $request->get('occupant_nicno')]];
        }
        if ($request->filled('contactno')) {
            $params['body']['query']['bool']['must'][] = ['term' => ['contactno' => $request->get('contactno')]];
        }
        if ($request->filled('name')) {
            $params['body']['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $request->get('name'),
                    'fields' => ['name^2', 'fname']
                ]
            ];
        }
    
        try {
            $response = $client->search($params);
    
            $total = $response['hits']['total']['value'];
            $searchResults = $response['hits']['hits'];
    
            $totalPages = ceil($total / $perPage);

            $searchResults = $this->attachDatabaseIds($searchResults);
    
            return [$searchResults, $total, $totalPages];
        } catch (\Exception $e) {
            return [[], 0, 0];
        }
    }

    /**
     * Map Elasticsearch search results to canonical MySQL database IDs using reference_no & bill_month.
     */
    private function attachDatabaseIds(array $hits): array
    {
        $refNumbers = collect($hits)
            ->map(function ($item) {
                if (isset($item['_source']['reference_no'])) {
                    return $item['_source']['reference_no'];
                }
                if (is_array($item) && isset($item['reference_no'])) {
                    return $item['reference_no'];
                }
                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($refNumbers)) {
            return $hits;
        }

        $dbConsumers = Consumer::whereIn('reference_no', $refNumbers)
            ->select(['id', 'reference_no', 'bill_month'])
            ->get();

        $consumersByRef = [];
        $consumersByRefAndMonth = [];

        foreach ($dbConsumers as $dbConsumer) {
            $consumersByRef[$dbConsumer->reference_no] = $dbConsumer->id;
            if ($dbConsumer->bill_month) {
                $consumersByRefAndMonth[$dbConsumer->reference_no . '_' . $dbConsumer->bill_month] = $dbConsumer->id;
            }
        }

        foreach ($hits as &$hit) {
            $ref = $hit['_source']['reference_no'] ?? ($hit['reference_no'] ?? null);
            $month = $hit['_source']['bill_month'] ?? ($hit['bill_month'] ?? null);

            $resolvedId = null;
            if ($ref && $month && isset($consumersByRefAndMonth[$ref . '_' . $month])) {
                $resolvedId = $consumersByRefAndMonth[$ref . '_' . $month];
            } elseif ($ref && isset($consumersByRef[$ref])) {
                $resolvedId = $consumersByRef[$ref];
            }

            if ($resolvedId) {
                if (isset($hit['_source'])) {
                    $hit['_source']['id'] = $resolvedId;
                }
                if (is_array($hit)) {
                    $hit['db_id'] = $resolvedId;
                    if (isset($hit['id']) || !isset($hit['_source'])) {
                        $hit['id'] = $resolvedId;
                    }
                }
            }
        }
        unset($hit);

        return $hits;
    }

    /**
     * Resiliently resolve consumer by database ID, reference_no, or Elasticsearch document ID.
     */
    private function resolveConsumer($id): Consumer
    {
        $consumer = Consumer::find($id);

        if (!$consumer) {
            $consumer = Consumer::where('reference_no', $id)->first();
        }

        if (!$consumer) {
            try {
                $doc = $this->client->get([
                    'index' => 'consumers',
                    'id'    => $id,
                ]);
                if (isset($doc['_source']['reference_no'])) {
                    $consumer = Consumer::where('reference_no', $doc['_source']['reference_no'])->first();
                }
            } catch (\Exception $e) {
                // Not found in Elasticsearch either
            }
        }

        if (!$consumer) {
            abort(404, 'Consumer not found');
        }

        return $consumer;
    }


    public function create()
    {
        $columns = [
            'reference_no', 'bill_month', 'name', 'fname', 'address_1', 'address_2', 'corporation_name', 'connection_date',
            'season_dode', 'season_age', 'fata_pata_code', 'it_exempt_code', 'extra_tax_exempt_code', 'meter_rent', 'service_rent',
            'meter_phase', 'feeder_code', 'feeder_name', 'transformer_code', 'tranformer_address', 'wapda_employee_bps_code',
            'wapda_employee_name', 'wapda_department_code', 'wapda_employee_epf_no', 'wapda_employee_balance_units',
            'contract_expire_date', 'appliation_date', 'security_date', 'security_amount', 'nicno', 'emailaddr', 'contactno',
            'no_of_ac', 'no_of_tv', 'ntn_no', 'strn_no', 'no_of_booster', 'no_of_poles', 'current_status', 'defalter_level',
            'defalter_age', 'disconnection_issue_no', 'disconnection_issue_date', 'disconnection_expiry_date', 'disconection_age',
            'same_age', 'kwh_meter_defective_age', 'total_deffered_amount', 'total_installemnt', 'remaining_installment',
            'last_disconnection_date', 'last_reconnection_date', 'last_defective_date', 'last_replacement_date', 'defective_times',
            'replacement_times', 'defective_remaning_times', 'agriculture_motor_code', 'tv_exempt_code', 'uniqkey', 'old_reference_no',
            'old_reference_change_date', 'gps_longitude', 'gps_latitude', 'sub_batch', 'tariff', 'sanction_load', 'connected_load',
            'rural_uraban_code', 'standard_classification_code', 'total_kwh_meter', 'govt_department_code', 'electricity_duty_code', 'occupant_nicno'
        ];
        return view('consumers.create', compact('columns'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $refNo = $data['reference_no'];
        $data['subdivision_code'] = substr($refNo, 2, 5);

        $consumer = Consumer::create($data);

        $params = [
            'index' => 'consumers',
            'id'    => $consumer->id,
            'body'  => $consumer->toArray(),
        ];

        $indexed = false;
        try {
            $this->client->index($params);
            $indexed = true;
        } catch (\Exception $e) {
            Log::error("Elasticsearch index error: " . $e->getMessage());
        }

        $importLog = ImportLog::firstOrCreate(
            ['bill_month' => $consumer->bill_month],
            ['consumers_count' => 0, 'subdivisions_count' => 0, 'indexed_count' => 0]
        );
        $importLog->increment('consumers_count');

        if ($indexed) {
            $importLog->increment('indexed_count');
        }

        $subdivision = ImportLogSubdivision::firstOrCreate(
            ['import_log_id' => $importLog->id, 'subdivision_code' => $consumer->subdivision_code],
            ['consumers_count' => 0, 'indexed_count' => 0]
        );
        $subdivision->increment('consumers_count');

        if ($indexed) {
            $subdivision->increment('indexed_count');
        }

        Cache::forget('consumers_total_count');

        return redirect()->route('consumers.index')->with('success', 'Consumer added successfully');
    }

    public function edit($id)
    {
        $consumer = $this->resolveConsumer($id);

        if ((string) $consumer->id !== (string) $id) {
            return redirect()->route('consumers.edit', $consumer->id);
        }

        $columns = [
            'reference_no', 'bill_month', 'name', 'fname', 'address_1', 'address_2', 'corporation_name', 'connection_date',
            'season_dode', 'season_age', 'fata_pata_code', 'it_exempt_code', 'extra_tax_exempt_code', 'meter_rent', 'service_rent',
            'meter_phase', 'feeder_code', 'feeder_name', 'transformer_code', 'tranformer_address', 'wapda_employee_bps_code',
            'wapda_employee_name', 'wapda_department_code', 'wapda_employee_epf_no', 'wapda_employee_balance_units',
            'contract_expire_date', 'appliation_date', 'security_date', 'security_amount', 'nicno', 'emailaddr', 'contactno',
            'no_of_ac', 'no_of_tv', 'ntn_no', 'strn_no', 'no_of_booster', 'no_of_poles', 'current_status', 'defalter_level',
            'defalter_age', 'disconnection_issue_no', 'disconnection_issue_date', 'disconnection_expiry_date', 'disconection_age',
            'same_age', 'kwh_meter_defective_age', 'total_deffered_amount', 'total_installemnt', 'remaining_installment',
            'last_disconnection_date', 'last_reconnection_date', 'last_defective_date', 'last_replacement_date', 'defective_times',
            'replacement_times', 'defective_remaning_times', 'agriculture_motor_code', 'tv_exempt_code', 'uniqkey', 'old_reference_no',
            'old_reference_change_date', 'gps_longitude', 'gps_latitude', 'sub_batch', 'tariff', 'sanction_load', 'connected_load',
            'rural_uraban_code', 'standard_classification_code', 'total_kwh_meter', 'govt_department_code', 'electricity_duty_code', 'occupant_nicno'
        ];
        return view('consumers.edit', compact('consumer', 'columns'));
    }

    public function update(Request $request, $id)
    {
        $consumer = $this->resolveConsumer($id);
        $data = $request->all();
        $refNo = $data['reference_no'];
        $data['subdivision_code'] = substr($refNo, 2, 5);
        $original = $consumer->getOriginal();
        $consumer->update($data);
        $changes = [];

        foreach ($consumer->getChanges() as $field => $newValue) {
            if ($field !== "updated_at") {
                $oldValue = trim($original[$field] ?? '');

                $oldValue = $oldValue === '' ? '-' : $oldValue;
                $newValue = trim($newValue ?? '');
                $newValue = $newValue === '' ? '-' : $newValue;

                if ($oldValue !== $newValue) {
                    $changes[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        if (!empty($changes)) {
            ConsumerHistory::create([
                'consumer_id'    => $consumer->id,
                'updated_by'     => auth()->id() ?? null,
                'changed_fields' => json_encode($changes),
            ]);
        }

        $params = [
            'index' => 'consumers',
            'id'    => $consumer->id,
            'body'  => [
                'doc'           => $consumer->toArray(),
                'doc_as_upsert' => true,
            ],
        ];

        try {
            $this->client->update($params);

            if ((string) $id !== (string) $consumer->id) {
                try {
                    $this->client->delete(['index' => 'consumers', 'id' => $id]);
                } catch (\Exception $e) {}
            }
        } catch (\Exception $e) {
            Log::error("Elasticsearch update error: " . $e->getMessage());
        }

        return redirect()->route('consumers.index')->with('success', 'Consumer updated successfully');
    }

    public function destroy($id)
    {
        $consumer = $this->resolveConsumer($id);

        $billMonth = $consumer->bill_month;
        $subdivisionCode = $consumer->subdivision_code;
        $canonicalId = $consumer->id;

        $consumer->delete();

        $params = [
            'index' => 'consumers',
            'id'    => $canonicalId
        ];

        $deletedFromIndex = false;
        try {
            $this->client->delete($params);
            $deletedFromIndex = true;
        } catch (\Exception $e) {
            Log::error("Elasticsearch delete error: " . $e->getMessage());
        }

        if ((string) $id !== (string) $canonicalId) {
            try {
                $this->client->delete(['index' => 'consumers', 'id' => $id]);
            } catch (\Exception $e) {}
        }

        if ($importLog = ImportLog::where('bill_month', $billMonth)->first()) {
            $importLog->decrement('consumers_count');
            if ($deletedFromIndex) {
                $importLog->decrement('indexed_count');
            }
        }

        if ($importLog) {
            if ($subdivision = ImportLogSubdivision::where([
                'import_log_id'    => $importLog->id,
                'subdivision_code' => $subdivisionCode
            ])->first()) {
                $subdivision->decrement('consumers_count');
                if ($deletedFromIndex) {
                    $subdivision->decrement('indexed_count');
                }
            }
        }

        Cache::forget('consumers_total_count');

        return redirect()->route('consumers.index')->with('success', 'Consumer deleted successfully');
    }


    public function elasticSearch(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'reference_no' => 'required_without_all:cnic,contactno|digits:14',
            'cnic'         => 'required_without_all:reference_no,contactno|digits:13',
            'contactno'    => 'required_without_all:reference_no,cnic|digits:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'total' => 0,
                'results' => [],
                'errors' => $validator->errors(),
            ], 422); // Unprocessable Entity
        }

        $page = $request->get('page', 1);
        $perPage = 10;

        $params = [
            'index' => 'consumers',
            'body'  => [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'must' => [],
                        'filter' => []
                    ]
                ]
            ]
        ];

        if ($request->filled('reference_no')) {
            $params['body']['query']['bool']['filter'][] = [
                'term' => [
                    'reference_no' => [
                        'value' => $request->get('reference_no'),
                        'boost' => 1
                    ]
                ]
            ];
        }

        if ($request->filled('cnic')) {
            $params['body']['query']['bool']['filter'][] = [
                'term' => [
                    'occupant_nicno' => [
                        'value' => $request->get('cnic'),
                        'boost' => 1
                    ]
                ]
            ];
        }

        if ($request->filled('contactno')) {
            $params['body']['query']['bool']['filter'][] = [
                'term' => [
                    'contactno' => [
                        'value' => $request->get('contactno'),
                        'boost' => 1
                    ]
                ]
            ];
        }

        try {
            $response = $this->client->search($params);

            $results = array_map(fn($hit) => $hit['_source'], $response['hits']['hits']);
            $results = $this->attachDatabaseIds($results);

            return response()->json([
                'total' => $response['hits']['total']['value'],
                'results' => $results,
                'query' => $params['body']['query']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'total' => 0,
                'results' => [],
                'query' => $params['body']['query'],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'importFile' => 'required|mimes:csv,txt|max:204800'
        ]);

        $trackingId = Str::uuid()->toString();
        $savedPath = $request->file('importFile')
        ->storeAs('imports', $request->file('importFile')->getClientOriginalName());

        dispatch(new ImportAndIndexConsumers($savedPath, $trackingId));

        return response()->json([
            'tracking_id' => $trackingId
        ]);
    }

    public function importSummary($trackingId)
    {
        $summary = Cache::get("import_summary_{$trackingId}");

        if (!$summary) {
            return response()->json(['status' => 'running']);
        }

        return response()->json($summary);
    }

    public function allHistories()
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator $histories */
        $histories = ConsumerHistory::with(['consumer', 'user'])
            ->latest()
            ->paginate(20);

        // Ensure changed_fields is always an array
        $histories->getCollection()->transform(function ($history) {
            if (is_string($history->changed_fields)) {
                $decoded = json_decode($history->changed_fields, true);
                $history->changed_fields = $decoded ?: [];
            }
            return $history;
        });

        return view('consumers.histories-all', compact('histories'));
    }

    public function consumerHistory($consumerId)
    {
        $consumer = Consumer::find($consumerId);
        if (!$consumer) {
            $consumer = Consumer::where('reference_no', $consumerId)->first();
        }
        if (!$consumer) {
            try {
                $doc = $this->client->get([
                    'index' => 'consumers',
                    'id'    => $consumerId,
                ]);
                if (isset($doc['_source']['reference_no'])) {
                    $consumer = Consumer::where('reference_no', $doc['_source']['reference_no'])->first();
                }
            } catch (\Exception $e) {}
        }

        $canonicalId = $consumer ? $consumer->id : $consumerId;

        $histories = ConsumerHistory::with(['consumer', 'user'])
            ->where('consumer_id', $canonicalId)
            ->latest()
            ->paginate(20);

        $histories->getCollection()->transform(function ($history) {
            if (is_string($history->changed_fields)) {
                $decoded = json_decode($history->changed_fields, true);
                $history->changed_fields = $decoded ?: [];
            }
            return $history;
        });

        return view('consumers.histories-all', compact('histories'));
    }
}
