<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consumers', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 100)->nullable();
            $table->index('reference_no');
            $table->string('bill_month', 100)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('fname', 255)->nullable();
            $table->text('address_1')->nullable();
            $table->text('address_2')->nullable();
            $table->string('corporation_name', 255)->nullable();
            $table->string('connection_date', 100)->nullable();
            $table->string('season_dode', 100)->nullable();
            $table->string('season_age', 100)->nullable();
            $table->string('fata_pata_code', 100)->nullable();
            $table->string('it_exempt_code', 100)->nullable();
            $table->string('extra_tax_exempt_code', 100)->nullable();
            $table->string('meter_rent', 100)->nullable();
            $table->string('service_rent', 100)->nullable();
            $table->string('meter_phase', 100)->nullable();
            $table->string('feeder_code', 100)->nullable();
            $table->string('feeder_name', 100)->nullable();
            $table->string('transformer_code', 100)->nullable();
            $table->string('tranformer_address', 255)->nullable();
            $table->string('wapda_employee_bps_code', 100)->nullable();
            $table->string('wapda_employee_name', 255)->nullable();
            $table->string('wapda_department_code', 100)->nullable();
            $table->string('wapda_employee_epf_no', 100)->nullable();
            $table->string('wapda_employee_balance_units', 100)->nullable();
            $table->string('contract_expire_date', 100)->nullable();
            $table->string('appliation_date', 100)->nullable();
            $table->string('security_date', 100)->nullable();
            $table->string('security_amount', 100)->nullable();
            $table->string('nicno', 100)->nullable();
            $table->string('emailaddr', 255)->nullable();
            $table->string('contactno', 100)->nullable();
            $table->string('no_of_ac', 100)->nullable();
            $table->string('no_of_tv', 100)->nullable();
            $table->string('ntn_no', 100)->nullable();
            $table->string('strn_no', 100)->nullable();
            $table->string('no_of_booster', 100)->nullable();
            $table->string('no_of_poles', 100)->nullable();
            $table->string('current_status', 100)->nullable();
            $table->string('defalter_level', 100)->nullable();
            $table->string('defalter_age', 100)->nullable();
            $table->string('disconnection_issue_no', 100)->nullable();
            $table->string('disconnection_issue_date', 100)->nullable();
            $table->string('disconnection_expiry_date', 100)->nullable();
            $table->string('disconection_age', 100)->nullable();
            $table->string('same_age', 100)->nullable();
            $table->string('kwh_meter_defective_age', 100)->nullable();
            $table->string('total_deffered_amount', 100)->nullable();
            $table->string('total_installemnt', 100)->nullable();
            $table->string('remaining_installment', 100)->nullable();
            $table->string('last_disconnection_date', 100)->nullable();
            $table->string('last_reconnection_date', 100)->nullable();
            $table->string('last_defective_date', 100)->nullable();
            $table->string('last_replacement_date', 100)->nullable();
            $table->string('defective_times', 100)->nullable();
            $table->string('replacement_times', 100)->nullable();
            $table->string('defective_remaning_times', 100)->nullable();
            $table->string('agriculture_motor_code', 100)->nullable();
            $table->string('tv_exempt_code', 100)->nullable();
            $table->string('uniqkey', 100)->nullable();
            $table->string('old_reference_no', 100)->nullable();
            $table->string('old_reference_change_date', 100)->nullable();
            $table->string('gps_longitude', 100)->nullable();
            $table->string('gps_latitude', 100)->nullable();
            $table->string('sub_batch', 100)->nullable();
            $table->string('tariff', 100)->nullable();
            $table->string('sanction_load', 100)->nullable();
            $table->string('connected_load', 100)->nullable();
            $table->string('rural_uraban_code', 100)->nullable();
            $table->string('standard_classification_code', 100)->nullable();
            $table->string('total_kwh_meter', 100)->nullable();
            $table->string('govt_department_code', 100)->nullable();
            $table->string('electricity_duty_code', 100)->nullable();
            $table->string('occupant_nicno', 100)->nullable();
            $table->string('application_no', 100)->nullable();
            

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumers');
    }
};

