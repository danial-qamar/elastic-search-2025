<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Elasticsearch\ClientBuilder;


class Consumer extends Model
{
    use HasFactory;

    protected $table = 'consumers';

    protected $fillable = [
        'reference_no', 'name', 'bill_month', 'fname', 'address_1', 'address_2', 'corporation_name', 'connection_date', 'season_dode', 'season_age', 
        'fata_pata_code', 'it_exempt_code', 'extra_tax_exempt_code', 'meter_rent', 'service_rent', 'meter_phase', 'feeder_code', 'feeder_name', 
        'transformer_code', 'tranformer_address', 'wapda_employee_bps_code', 'wapda_employee_name', 'wapda_department_code', 'wapda_employee_epf_no', 
        'wapda_employee_balance_units', 'contract_expire_date', 'appliation_date', 'security_date', 'security_amount', 'nicno', 'emailaddr', 
        'contactno', 'no_of_ac', 'no_of_tv', 'ntn_no', 'strn_no', 'no_of_booster', 'no_of_poles', 'current_status', 'defalter_level', 'defalter_age', 
        'disconnection_issue_no', 'disconnection_issue_date', 'disconnection_expiry_date', 'disconection_age', 'same_age', 'kwh_meter_defective_age', 
        'total_deffered_amount', 'total_installemnt', 'remaining_installment', 'last_disconnection_date', 'last_reconnection_date', 'last_defective_date', 
        'last_replacement_date', 'defective_times', 'replacement_times', 'defective_remaning_times', 'agriculture_motor_code', 'tv_exempt_code', 
        'uniqkey', 'old_reference_no', 'old_reference_change_date', 'gps_longitude', 'gps_latitude', 'sub_batch', 'tariff', 'sanction_load', 
        'connected_load', 'rural_uraban_code', 'standard_classification_code', 'total_kwh_meter', 'govt_department_code', 'electricity_duty_code', 
        'occupant_nicno',
    ];

    // Elasticsearch Mappings (Field types)
    public function mappableAs(): array
    {
        return [
            "reference_no" => 'keyword',
            "bill_month" => 'keyword',
            "name" => 'text',
            "fname" => 'text',
            "address_1" => 'text',
            "address_2" => 'text',
            "corporation_name" => 'text',
            "connection_date" => 'keyword',
            "season_dode" => 'keyword',
            "season_age" => 'keyword',
            "fata_pata_code" => 'keyword',
            "it_exempt_code" => 'keyword',
            "extra_tax_exempt_code" => 'keyword',
            "meter_rent" => 'keyword',
            "service_rent" => 'keyword',
            "meter_phase" => 'keyword',
            "feeder_code" => 'keyword',
            "feeder_name" => 'text',
            "transformer_code" => 'keyword',
            "tranformer_address" => [
                'type' => 'text',
                'fields' => [
                    'raw' => [
                        'type' => 'keyword',
                        'ignore_above' => 256,
                    ]
                ]
            ],
            "sub_batch" => 'keyword',  // Fixed line
            "tariff" => 'keyword',
            "sanction_load" => 'keyword',
            "connected_load" => 'keyword',
            "rural_uraban_code" => 'keyword',
            "standard_classification_code" => 'keyword',
            "total_kwh_meter" => 'keyword',
            "govt_department_code" => 'keyword',
            "electricity_duty_code" => 'keyword',
            "occupant_nicno" => 'keyword',
        ];
    }
    

    public function indexSettings(): array
    {
        return [
            'index' => [
                'number_of_shards' => 5,
                'number_of_replicas' => 2,
            ],
        ];
    }

    public function getElasticsearchClient()
    {
        return ClientBuilder::create()->build();
    }

    public function createIndexWithMappings()
    {
        $client = $this->getElasticsearchClient();
        $params = [
            'index' => 'consumers',
            'body'  => [
                'mappings' => [
                    'properties' => $this->mappableAs(),
                ],
                'settings' => $this->indexSettings(),
            ],
        ];
        return $client->indices()->create($params);
    }
    

    public static function searchDocument($query)
    {
        $client = (new static)->getElasticsearchClient();
        $params = [
            'index' => 'consumers',
            'body' => [
                'query' => [
                    'match' => [
                        'name' => $query,
                    ],
                ],
            ],
        ];

        return $client->search($params);
    }
}

