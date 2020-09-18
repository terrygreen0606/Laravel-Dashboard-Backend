<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class NoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $user = User::where('id',$this->user_id)->first(); 
        /*return [
            'id' => $this->id,
            'note' => $this->note,
            'user' => $this->user->first_name . ' ' . $this->user->last_name,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i') . ' - (' . $this->created_at->diffForHumans() . ')' : '-//-'
        ];*/

        /* new  */
        return [
            'id' => $this->id,
            'note' => $this->note,
            'user' => $user->first_name . ' ' . $user->last_name,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i') . ' - (' . $this->created_at->diffForHumans() . ')' : '-//-'
        ];
    }
}
