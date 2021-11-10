<?php

namespace App\Http\Controllers;

use App\ChatRoom;
use App\Department;
use App\Events\NewChatRoom;
use App\Events\ReadMessage;
use App\Events\UpdateNameChatRoom;
use App\Message;
use App\User;
use App\UserTemplateModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Integer;

class ChatRoomsController extends Controller
{
    /**
     * @var int clave del módulo
     */
    private $module_key = 14;

    /**
     * @mixed Obejeto con la información del módulo actual para la plantilla del cuenta indicado
     */
    private $module;

    /**
     * @author Roger Corominas
     * AccountsController constructor.
     * Asignamos el objeto módulo al atributo module.
     */
    public function __construct() {
        $user_template_id = 1;
        $this->module = UserTemplateModule::generateQueryModuleByUserTempalateModuleKey($user_template_id, $this->module_key)
            ->first();
    }

    /**
     * @author Roger Corominas
     * Función para crear un chat por el API
     * @param $request(message_type_id, name, users, departments, account_id)
     */
    public function api_store(Request $request)
    {
        $data = $request->all();
        $message_type_id = $data['message_type_id'];
        $name = $data['name'];
        $users = $data['users'];
        $departments = $data['departments'];
        if (!empty($data['account_id'])) {
            $account_id = $data['account_id'];
        } else {
            $account_id = null;
        }
        $from = '@internal';
        $to = '@internal';

        $chat_room = createChatRoom($message_type_id, $name, $users, $departments, $from, $to, $account_id);

        return $chat_room->load('departments', 'users', 'accounts', 'users.active_session');
    }

    public function api_update_name(Request $request) {
        if(!empty($request->name) && !empty($request->chat_room_id)) {
            DB::update('UPDATE chat_rooms SET name = "'.$request->name.'" WHERE id = '.$request->chat_room_id);
            DB::update('UPDATE user_chat_room SET name = "'.$request->name.'" WHERE chat_room_id = '.$request->chat_room_id);
            DB::update('UPDATE contact_chat_room SET name = "'.$request->name.'" WHERE chat_room_id = '.$request->chat_room_id);
            DB::update('UPDATE account_chat_room SET name = "'.$request->name.'" WHERE chat_room_id = '.$request->chat_room_id);

            $chat_room = ChatRoom::findOrFail($request->chat_room_id);
            foreach($chat_room->users as $user) {
                broadcast(new UpdateNameChatRoom($chat_room->id, $request->name, 'App.User.'.$user->id));
            }

            return ['error' => 0];
        } else {
            return ['error' => 1];
        }
    }

    public function api_add_component(Request $request) {
        if(
            !empty($request->component)
            && !empty($request->component['id'])
            && !empty($request->component['name'])
            && !empty($request->component['type'])
            && !empty($request->chat_room_id)
        ) {

            $chat_room = ChatRoom::findOrFail($request->chat_room_id);
            $chat_room = chat_room_add_compoenent($chat_room, $request->component);

            return $chat_room->load('departments', 'users', 'accounts', 'contacts', 'users.active_session');
        } else {
            return ['error' => 1];
        }
    }

    /**
     * @author Roger Corominas
     * Devuelve un listado con todos los chats del tipo indicado para el usuario logeado
     * @param int $message_type_id Identificador del tipo de chats
     * @return mixed
     */
    public function api_get_user_chat_rooms(int $message_type_id = 0)
    {
        $user = Auth::user();
        if (!empty($message_type_id)) {
            return $user->chat_rooms()
                ->where('chat_rooms.message_type_id', $message_type_id)
                ->where('chat_rooms.is_active', 1)
                ->get()
                ->load('departments', 'users', 'accounts', 'contacts', 'users.active_session');
        } else {
            return $user->chat_rooms()
                ->where('chat_rooms.is_active', 1)
                ->get()
                ->load('departments', 'users', 'accounts', 'contacts', 'users.active_session');
        }
    }

    public function api_reset_unread_messages(int $chat_room_id) {
        $user = Auth::user();

        DB::update('UPDATE messages SET read_user_id = '.$user->id.' WHERE chat_room_id = '.$chat_room_id. ' AND read_user_id is null');
        DB::update('UPDATE user_chat_room SET unread = 0, last_read_message_id = NOW() WHERE chat_room_id = ' . $chat_room_id . ' AND user_id = ' . $user->id . ' AND unread > 0');
        DB::update('UPDATE user_chat_room SET last_connection_at = NOW() WHERE chat_room_id = ' . $chat_room_id . ' AND user_id = ' . $user->id);

        return $user->chat_rooms()->where('chat_rooms.id', $chat_room_id)->first()->load('departments', 'users', 'accounts', 'users.active_session');

    }

    public function api_close_chat_room(int $id) {
        $chat_room = ChatRoom::findOrFail($id);
        $chat_room->is_active = 0;
        $chat_room->save();

        return $chat_room;
    }
}
