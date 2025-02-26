<?php

namespace App\Scout\Engines;

use Elastic\Elasticsearch\Client;
use Laravel\Scout\Engines\Engine;
use Laravel\Scout\Builder;

class ElasticsearchEngine extends Engine
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function update($models)
    {
        // Update logic
    }

    public function delete($models)
    {
        // Delete logic
    }

    public function search($query)
    {
        // Search logic
    }

    public function createIndex($name, array $options = [])
    {
        return $this->client->indices()->create([
            'index' => $name,
            'body'  => $options,
        ]);
    }

    public function deleteIndex($name)
    {
        return $this->client->indices()->delete(['index' => $name]);
    }

    public function flush($model)
    {
        return $this->client->indices()->refresh([
            'index' => $this->getIndexName($model),
        ]);
    }

    public function getTotalCount($results)
    {
        return $results['hits']['total']['value'] ?? 0;
    }

    public function lazyMap(Builder $builder, $results, $model)
    {
        return collect($results['hits']['hits'])->map(function ($hit) use ($model) {
            return new $model($hit['_source']);
        });
    }

    public function map(Builder $builder, $results, $model)
    {
        return collect($results['hits']['hits'])->map(function ($hit) use ($model) {
            return new $model($hit['_source']);
        });
    }

    public function mapIds($results)
    {
        return collect($results['hits']['hits'])->pluck('_id');
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        $from = ($page - 1) * $perPage;

        $results = $this->client->search([
            'index' => $this->getIndexName($builder->model),
            'body'  => [
                // 'query' => $builder->query(),
                'from'  => $from,
                'size'  => $perPage,
            ],
        ]);

        return $this->map($builder, $results, $builder->model);
    }

    private function getIndexName($model)
    {
        return $model->searchableAs();
    }
}
