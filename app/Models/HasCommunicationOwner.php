<?php

namespace App\Models;

/**
 * Implemented by every actor that can participate in the Comunicaciones module
 * (the contador himself and his invited TeamMembers), so channels/messages can
 * resolve which contador's workspace an actor belongs to regardless of type.
 */
interface HasCommunicationOwner
{
    public function communicationOwnerId(): int;
}
