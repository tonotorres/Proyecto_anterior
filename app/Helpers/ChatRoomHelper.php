<?php

use App\Account;
use App\ChatRoom;
use App\Contact;
use App\Department;
use App\Events\NewChatRoom;
use App\User;
use Illuminate\Support\Facades\Storage;

if (!function_exists('createChatRoom')) {
    /**
     * Función para crear un chat, enlazarlo con todos los componentes y notificarlo a todos los usuarios
     * @author Roger Corominas
     * @version 2.0.0
     * @param int $company_id Empresa a la que agregar el chat
     * @param int $message_type_id Tipo de mensaje del chat
     * @param String name nombre del chat
     * @param Array $users array de usuarios que tienen acceso al chat
     * @param Array $departments array de departamentos que tienen acceso al chat
     * @param String $from desde donde se originará los mensajes internos (pensado para comunicaciones externas Whatsapp, SMS, etc)
     * @param String $to a donde se mandarán los mansajes (pensado para comunicaciones externas Whatsapp, SMS, etc)
     * @param int account_id (opcional) identificador de la cuenta que tiene acceso al chat
     * @param int externalid (opcional) identificador externo para el chat
     */
    function createChatRoom(int $company_id, int $message_type_id, String $name, array $users, array $departments, String $from, String $to, int $account_id = null, string $externalid = null)
    {
        //RC: Generamos el chat
        $chatRoom = ChatRoom::create([
            'company_id' => $company_id,
            'message_type_id' => $message_type_id,
            'account_id' => $account_id,
            'externalid' => $externalid,
            'name' => $name,
            'from' => $from,
            'to' => $to,
            'is_active' => 1
        ]);

        //RC: Enlazamos los diferentes usuarios
        if (!empty($users)) {
            //RC: si no tenemos nombre la los usuarios y son 2 tenemos que indicar el nombre del otro
            if (count($users) == 2 && empty($name)) {
                $names[0] = $users[1]->name;
                $names[1] = $users[0]->name;
            }

            foreach ($users as $index => $user) {
                //RC: Si tenemos el array de nombres lo tenemos que setear
                if (!empty($names)) {
                    $name = $names[$index];
                }

                $chatRoom->users()->attach($user->id, [
                    'name' => $name,
                    'unread' => 0,
                    'last_connection_at' => null,
                    'last_read_message_id' => null
                ]);
            }
        }

        //RC: Enlazamos los diferentes departamentos
        foreach ($departments as $department) {
            Storage::append('test', json_encode($department));
            $chatRoom->departments()->attach($department->id, [
                'last_connection_at' => null,
                'last_read_message_id' => null
            ]);

            foreach ($department->users as $user) {
                $chatRoom->users()->attach($user->id, [
                    'name' => $name,
                    'unread' => 0,
                    'last_connection_at' => null,
                    'last_read_message_id' => null
                ]);
            }
        }

        //RC: Lo notificamos a los usuarios
        foreach ($users as $user) {
            broadcast(new NewChatRoom($chatRoom->load('departments', 'users', 'accounts', 'users.active_session'), 'App.User.' . $user->id));
        }


        //RC: Lo notificamos a los departamentos
        foreach ($departments as $department) {
            broadcast(new NewChatRoom($chatRoom->load('departments', 'users', 'accounts', 'users.active_session'), 'App.Department.' . $department->id));
        }

        return $chatRoom;
    }
}
/**
 * @author Roger Corominas
 * Generamos una nueva sala de chat del tipo indicado en message_type_id con el nombre indicado para los participantes facilitados
 * @param int $message_type_id
 * @param String $name
 * @param array $components
 */
if(!function_exists('create_chat_room')) {
    function create_chat_room(int $message_type_id, String $name, Array $components = array())
    {
        $chat_room = new ChatRoom();
        $chat_room->company_id = 1;
        $chat_room->message_type_id = $message_type_id;
        if(!empty($name)) {
            $chat_room->name = $name;
        } else if(count($components) == 1 && $components[0]['type'] == 'department') {
            $department = Department::findOrFail($components[0]['id']);
            $chat_room->name = $department->name;
        }
        $chat_room->is_active = 1;
        $chat_room->save();

        //RC: si tenemos usuarios los tenemos que asignar
        if(count($components) == 1 && $components[0]['type'] == 'department') {
            $department = Department::findOrFail($components[0]['id']);
            $name = $department->name;
        } else if (
            empty($name) && count($components) == 2 && $components[0]['type'] != 'department' && $components[1]['type'] != 'department'
        ) {
            $names[0] = $components[1]['name'];
            $names[1] = $components[0]['name'];
        } else {
            if(empty($name)) {
                return ['error' => ['name' => 'Este campo es obligatorio']];
            }
        }

        $i = 0;
        foreach($components as $component) {
            // RC: Asignamos el nombre en caso de tener un chat "individual"
            if(!empty($names[$i])) {
                $name = $names[$i];
            }

            $chat_room = chat_room_add_component($chat_room, $name, $component);
            $i++;
        }

        return $chat_room;
    }
}

if(!function_exists('chat_room_add_component')) {
    function chat_room_add_component($chat_room, $name, $component) {
        switch ($component['type']) {
            case 'user':
                $user = User::findOrFail($component['id']);
                $object_user['name'] = $name;
                $object_user['unread'] = 0;

                $chat_room->users()->attach($user->id, $object_user);
                broadcast(new NewChatRoom($chat_room->load('departments', 'users', 'accounts', 'users.active_session'), 'App.User.' . $user->id));
                break;
            case 'department':
                $department = Department::findOrFail($component['id']);
                $object_department['department_id'] = $component['id'];
                $chat_room->departments()->attach($object_department);

                foreach ($department->users as $user) {
                    $object_user['name'] = $name;
                    $object_user['unread'] = 0;

                    $chat_room->users()->attach($user->id, $object_user);

                    broadcast(new NewChatRoom($chat_room->load('departments', 'users', 'accounts', 'users.active_session'), 'App.User.' . $user->id));
                }
                break;
            case 'contact':
                $contact = Contact::findOrFail($component['id']);
                $object_contact['name'] = $name;
                $object_contact['unread'] = 0;

                $chat_room->contacts()->attach($contact->id, $object_contact);
                break;
            case 'account':

                break;
        }

        return $chat_room->load('users', 'departments', 'accounts', 'contacts', 'last_messages');
    }
}

if(!function_exists('chat_room_remove_component')) {
    function chat_room_remove_component($chat_room, $component) {
        switch ($component['type']) {
            case 'user':
                $chat_room->users()->detach($component['id']);
                break;
            case 'department':
                $department = Department::findOrFail($component['id']);
                $chat_room->departments()->detach($component['id']);

                foreach ($department->users as $user) {
                    $chat_room->users()->detach($user->id);
                }
                break;
            case 'contact':
                $chat_room->contacts()->detach($component['id']);
                break;
            case 'account':

                break;
        }

        return $chat_room->load('users', 'departments', 'accounts', 'contacts', 'last_messages');
    }
}

if (!function_exists('getChatRoomByAccount')) {
    function getChatRoomByAccount(int $companyId, int $messageTypeId, $department, int $accountId, String $from, String $to, bool $create = false)
    {
        if (!empty($department)) {
            $chatRoom = ChatRoom::join('department_chat_room', 'department_chat_room.chat_room_id', '=', 'chat_rooms.id')
                ->where('chat_rooms.message_type_id', $messageTypeId)
                ->where('department_chat_room.department_id', $department->id)
                ->where('chat_rooms.account_id', $accountId)
                ->select('chat_rooms.*')
                ->first();
        } else {
            $chatRoom = ChatRoom::where('chat_rooms.message_type_id', $messageTypeId)
                ->where('chat_rooms.account_id', $accountId)
                ->select('chat_rooms.*')
                ->first();
        }

        if (empty($chatRoom) && $create) {
            $account = Account::findOrFail($accountId);
            $departments = [];
            if (!empty($department)) {
                $departments[] = $department;
            }

            $chatRoom = createChatRoom($companyId, $messageTypeId, $account->name, [], $departments, $from, $to, $account->id);
        }

        return $chatRoom;
    }
}

if(!function_exists('get_chat_room_by_department_contact')) {
    function get_chat_room_by_department_contact(int $message_type_id, $department_id, int $contact_id, bool $create = false) {
        if(!empty($department_id)) {
            $chat_room = ChatRoom::join('department_chat_room', 'department_chat_room.chat_room_id', '=', 'chat_rooms.id')
                ->join('contact_chat_room', 'contact_chat_room.chat_room_id', '=', 'chat_rooms.id')
                ->where('chat_rooms.message_type_id', $message_type_id)
                ->where('department_chat_room.department_id', $department_id)
                ->where('contact_chat_room.contact_id', $contact_id)
                ->select('chat_rooms.*')
                ->first();
        } else {
            $chat_rooms = ChatRoom::join('contact_chat_room', 'contact_chat_room.chat_room_id', '=', 'chat_rooms.id')
                ->where('chat_rooms.message_type_id', $message_type_id)
                ->where('contact_chat_room.contact_id', $contact_id)
                ->select('chat_rooms.*')
                ->get();

            //RC: Miramos si tenemos algún chat sin departamento
            $chat_room = null;
            if(count($chat_rooms) > 0) {
                foreach($chat_rooms as $cr) {
                    if($cr->departments()->count() == 0) {
                        //RC: Si no tenemos departamento lo asignamos
                        $chat_room = $cr;
                        break;
                    }
                }
            }
        }

        if(empty($chat_room) && $create) {
            $contact = Contact::findOrFail($contact_id);
            $components[] = ['id' => $contact['id'], 'name' => $contact['name'], 'type' => 'contact'];

            if(!empty($department_id)) {
                $department = Department::findOrFail($department_id);
                $components[] = ['id' => $department['id'], 'name' => $department['name'], 'type' => 'department'];
            }

            $chat_room = create_chat_room($message_type_id, $contact->name, $components);
            //RC: comunicamos el evento para todos los usuarios
            foreach($chat_room->users as $user) {
                broadcast(new NewChatRoom($chat_room, 'App.User.'.$user->id));
            }
        } else {
            $chat_room->load('users', 'departments', 'accounts', 'contacts', 'last_messages');
        }

        return $chat_room;
    }
}
