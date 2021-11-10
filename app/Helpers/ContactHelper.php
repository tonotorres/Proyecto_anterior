<?php

use App\ChatRoom;
use App\Contact;
use App\Department;
use App\User;

/**
 * @author Roger Corominas
 * Generamos una nueva sala de chat del tipo indicado en message_type_id con el nombre indicado para los participantes facilitados
 * @param int $message_type_id
 * @param String $name
 * @param array $components
 */
if(!function_exists('get_contact_by_contact_type')) {
    function get_contact_by_contact_type(string $value, int $contact_type_id, bool $create = false)
    {
        $contact = Contact::join('contact_contact_types', 'contact_contact_types.contact_id', '=', 'contacts.id')
            ->where('contact_contact_types.contact_type_id', $contact_type_id)
            ->where('contact_contact_types.value', 'like',  $value)
            ->select('contacts.*')
            ->first();

        if(empty($contact) && $create) {
            $contact = new Contact();
            $contact->company_id = 1;
            $contact->name = $value.'##AUTO##';
            $contact->save();

            $contact_contact_type = new \App\ContactContactType();
            $contact_contact_type['contact_type_id'] = $contact_type_id;
            $contact_contact_type['value'] = $value;
            $contact->contact_contact_types()->save($contact_contact_type);
        }

        return $contact;
    }
}
