<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    //
    // This will store the unserialized command, acting like a Singleton
    protected $unserializedCommand;

    public function getNameAttribute()
    {
        return $this->payload->displayName;
    }

    public function getMaxTries()
    {
        return $this->payload->maxTries;
    }

    // Decode the content of the payload since it's stored as JSON
    public function getPayloadAttribute()
    {
        return json_decode($this->attributes['payload']);
    }

    // Since Laravel stores the command serialized in database, this undo that serialization
    // and save the result into the singleton property to prevent unserializing again
    // (since it's a hard task)
    public function getCommandAttribute()
    {
        if (!$this->unserializedCommand) {
            $this->unserializedCommand = unserialize($this->payload->data->command);
        }

        return $this->unserializedCommand;
    }
}
