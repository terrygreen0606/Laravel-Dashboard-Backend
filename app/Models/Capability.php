<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Capability extends Model
{
    protected $table = 'capabilities';
    protected $guarded = [];

    protected $fillable = [
        'primary_service',
        'notes'
    ];

    public function updateValues($primary, $notes, $updateValues) {
        $this->primary_service = $primary;
        $this->notes = $notes;
        $this->save();
        if (!empty($updateValues) && count($updateValues) > 0) {
            foreach ($updateValues as $field => $value) {
                if ($field == "primary_service" ||
                    $field == "notes") continue;

                $field = CapabilityField::where('code', $field)->first();
                $fieldValue = $this->values->where('field_id', $field->id)->first();
                if (empty($value)) {
                    if (!empty($fieldValue)) {
                        $fieldValue->delete();
                    }
                    continue;
                }

                if (empty($fieldValue)) {
                    $fieldValue = new CapabilityValue;
                    $fieldValue->capabilities_id = $this->id;
                    $fieldValue->field_id = $field->id;
                }
                $fieldValue->value = $value;
                $fieldValue->save();
            }
        }
        return true;
    }

    public function values()
    {
        return $this->hasMany(CapabilityValue::class, 'capabilities_id', 'id');
    }

    public static function primaryServiceAvailable() {
        $fields = CapabilityField::select('id', 'code', 'label')->whereIn('field_type', [-1, 0, 1])->get();

        return $fields;
    }

    public function valuesAsAssoc() {
        $ret = [
            'primary_service' => $this->primary_service,
            'notes' => $this->notes
        ];

        $values = CapabilityValue::where('capabilities_id',$this->id)->get();
        if(count($values)>0){
            foreach ($values as $value) {
                        $ret[$value->field()->first()->code] = $value->value;
            }
        }else{
            $fields = CapabilityField::whereIn('field_type', [1,2])->get();
            foreach ($fields as $field) {
                $ret[$field->code] = null;
            }
        }

       /* $values = $this->values()->get();
        $fields = CapabilityField::whereIn('field_type', [1,2])->get();
        foreach ($fields as $field) {
            $ret[$field->code] = null;
        }
        foreach ($values as $value) {
            if(!isset($value)){
                $ret[$value->field()->first()->code] = $value->value;
            }else{

            }

        }*/
        return $ret;
    }
}
