<?php

namespace App\Helpers;

/* 
 * Wrapping serialize() and unserialize() in a class so we can mock them.
 */
class Serializer
{
    public function serialize($object)
    {
        return serialize($object);
    }

    public function unserialize($serializedObject)
    {
        return unserialize($serializedObject);
    }
}