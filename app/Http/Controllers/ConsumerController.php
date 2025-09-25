<?php

namespace App\Http\Controllers;

use App\Jobs\ImportAndIndexConsumers;
use Illuminate\Http\Request;
use App\Models\Consumer;
use App\Models\ImportLog;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
            return redirect()->route('consumers.index');
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
        $consumers = Consumer::paginate(10);
        return view('consumers.index', compact('consumers'));
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
    
            return [$searchResults, $total, $totalPages];
        } catch (\Exception $e) {
            return [[], 0, 0];
        }
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
        $consumer = Consumer::create($request->all());
        $params = [
            'index' => 'consumers',
            'id'    => $consumer->id,
            'body'  => $consumer->toArray(),
        ];

        try {
            $this->client->index($params);
        } catch (\Exception $e) {
            Log::error("Elasticsearch index error: " . $e->getMessage());
        }

        return redirect()->route('consumers.index')->with('success', 'Consumer added successfully');
    }

    public function edit($id)
    {
        $consumer = Consumer::findOrFail($id);
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
        $consumer = Consumer::findOrFail($id);
        $consumer->update($request->all());

        $params = [
            'index' => 'consumers',
            'id'    => $consumer->id,
            'body'  => [
                'doc' => $consumer->toArray()
            ],
        ];

        try {
            $this->client->update($params);
        } catch (\Exception $e) {
            Log::error("Elasticsearch update error: " . $e->getMessage());
        }

        return redirect()->route('consumers.index')->with('success', 'Consumer updated successfully');
    }

    public function destroy($id)
    {
        $consumer = Consumer::findOrFail($id);
        $consumer->delete();

        $params = [
            'index' => 'consumers',
            'id'    => $id
        ];

        try {
            $this->client->delete($params);
        } catch (\Exception $e) {
            Log::error("Elasticsearch delete error: " . $e->getMessage());
        }

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

}
