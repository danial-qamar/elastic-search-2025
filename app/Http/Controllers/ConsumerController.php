<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Consumer;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

class ConsumerController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()->build();
    }

    public function index(Request $request)
    {
        if ($request->has('name') || $request->has('contactno') || $request->has('reference_no') || $request->has('occupant_nicno')) {
            return $this->search($request); // Call the search method
        }
    
        $consumers = Consumer::paginate(10);
        return view('consumers.index', compact('consumers'));
    }
    
    public function search(Request $request)
    {
        // dd($request->all());
        $client = ClientBuilder::create()->build();
    
        $perPage = 10;
        $page = $request->get('page', 1);
    
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
                'term' => ['reference_no' => $request->get('reference_no')]
            ];
        }
    
        if ($request->filled('occupant_nicno')) {
            $params['body']['query']['bool']['must'][] = [
                'term' => ['occupant_nicno' => $request->get('occupant_nicno')]
            ];
        }
        if ($request->filled('contactno')) {
            $params['body']['query']['bool']['must'][] = [
                'term' => ['contactno' => $request->get('contactno')]
            ];
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
            $consumers = $response['hits']['hits'];
    
            $totalPages = ceil($total / $perPage);
    
            return view('consumers.index', compact('consumers', 'total', 'totalPages', 'page'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        return view('consumers.create');
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
        return view('consumers.edit', compact('consumer'));
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
}
