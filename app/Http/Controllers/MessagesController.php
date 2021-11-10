<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountContactType;
use App\ChatRoom;
use App\Company;
use App\CompanyConfig;
use App\Contact;
use App\ContactContactType;
use App\Department;
use App\DepartmentConfig;
use App\Events\NewMessage;
use App\Hook;
use App\MessageBody;
use App\User;
use App\Message;
use App\SeAccountToken;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Twilio\Rest\Client;

class MessagesController extends Controller
{
    private $report_department = false;
    private $account_id = null;
    private $token_id = null;
    
    public function api_get_unread_messages() {
        $user = Auth::user();

        //RC: Obtenemos todos los mensajes que no fueron leidos por el usuario
        $messages = Message::join('chat_rooms', 'chat_rooms.id', '=', 'messages.chat_room_id')
            ->join('user_chat_room', 'user_chat_room.chat_room_id', '=', 'chat_rooms.id')
            ->where('user_chat_room.user_id', '=', $user->id)
            ->whereRaw('(messages.write_user_id is NULL OR messages.write_user_id <> '.$user->id.')')
            ->whereRaw('NOT EXISTS(SELECT * FROM message_user WHERE user_id = '.$user->id.' AND message_id = messages.id)')
            ->select('messages.*')
            ->get()
            ->load('message_body', 'write_user', 'read_user', 'account', 'contact');

        return $messages;
    }

    public function api_get_chat_room_messages($chat_room_id, $page = 0) {
        $user = Auth::user();
        $chat_room = ChatRoom::findOrFail($chat_room_id);

        //RC: Validamos que el usuario tenga permisos para ver este chat
        if($chat_room->users()->where('id', $user->id)->count() == 0) {
            return ['error' => 1];
        }

        //RC: Devolvemos el listado de los mensajes
        return Message::where('chat_room_id', $chat_room_id)
            ->orderBy('id', 'desc')
            ->skip(($page * 50))
            ->limit(50)
            ->get()
            ->load('message_body', 'write_user', 'read_user', 'account', 'contact');
    }

    public function api_send(Request $request) {
        if(!empty($request->chat_room_id) && !empty($request->message_body_type_id)) {
            $user = Auth::user();
            return self::sendMessage($request->chat_room_id, $user->id, $request->message_body_type_id, $request->body);
        } else {
            return ['error' => true];
        }
    }

    public function api_send_template(Request $request) {
        if(!empty($request->chat_room_id) && !empty($request->message_body_type_id)) {
            $user = Auth::user();
            return self::sendMessageTemplate($request->chat_room_id, $user->id, $request->message_template, $request->params);
        } else {
            return ['error' => true];
        }
    }

    public function logWhatsappEvent(Request $request)
    {
        $name = 'whatstapp/' . date('Y') . '/' . date('m') . '/' . date('d') . '_log.txt';
        $data = $request->all();
        Storage::append($name, json_encode($data));
    }

    public function receiveWhatsappMessagebird(Request $request)
    {
        //RC: TEXT SAMPLE $data = json_decode('{"contact":{"attributes":[],"createdDatetime":"2020-06-30T16:55:41Z","customDetails":[],"displayName":"34690729978","firstName":null,"href":"https:\/\/contacts.messagebird.com\/v2\/contacts\/c4262f1054c249be971a756fd549ff00","id":"c4262f1054c249be971a756fd549ff00","lastName":null,"msisdn":34690729978,"updatedDatetime":"2020-06-30T17:16:34Z"},"conversation":{"contactId":"c4262f1054c249be971a756fd549ff00","createdDatetime":"2020-06-30T16:55:41Z","id":"0ea8f34d586544babaecc59bb5afcc1d","lastReceivedDatetime":"2020-07-01T10:20:26.066339206Z","status":"active","updatedDatetime":"2020-06-30T20:05:06.250036381Z"},"message":{"channelId":"09c3a21d853b4bdbaa1a605ead17c989","content":{"text":"Roger"},"conversationId":"0ea8f34d586544babaecc59bb5afcc1d","createdDatetime":"2020-07-01T10:20:25Z","direction":"received","from":"+34690729978","id":"48e710fef9d6453f80fcaaed88189e72","platform":"whatsapp","status":"received","to":"+34684412339","type":"text","updatedDatetime":"2020-07-01T10:20:26.072578638Z"},"type":"message.created"}', true);
        $name = 'whatstapp/' . date('Y') . '/' . date('m') . '/' . date('d') . '_log.txt';
        $data = $request->all();
        Storage::append($name, json_encode($data));

        if ($data['type'] != "message.created") {
            exit;
        }

        //RC: obtener la empresa en base al número
        $company = self::get_company_from_whatsapp_number($data['message']['to']);

        if (!empty($company)) {
            //RC: obtener el departamento en base al número
            $department = self::get_department_from_whatsapp_number($data['message']['from'], $data['message']['to'], $company->id);

            //RC: Obtenemos la cuenta
            $account_contact_type = get_account_type_by_number($company->id, $data['message']['from']);

            if (!empty($account_contact_type)) {
                //RC: Obtenemos la cuenta
                $account = Account::findOrFail($account_contact_type->account_id);
            } else {
                //RC: Generamos la cuenta
                $account = Account::create([
                    'company_id' => $company->id,
                    'name' => $data['contact']['displayName']
                ]);

                $account_contact_type = AccountContactType::create([
                    'account_id' => $account->id,
                    'contact_type_id' => 1,
                    'value' => $data['message']['from']
                ]);
            }

            //RC: Procesamos los hooks antes de proceso el mensaje
            $hooks = Hook::where('company_id', $company->id)
                ->where('key', 'BEFORE_RECEIVE_WHATSAPP_MESSAGE')
                ->where('active', 1)
                ->orderBy('position', 'asc')
                ->get();

            foreach ($hooks as $hook) {
                try {
                    eval($hook->code);
                } catch (Exception $e) {
                    $namehook = 'whatstapphooks/' . date('Y') . '/' . date('m') . '/' . date('d') . '_log.txt';
                    Storage::append($namehook, $e->getMessage());
                }
            }

            //RC: Miramos si ya tenemos un chat o generamos uno nuevo
            $chatRoom = getChatRoomByAccount($company->id, 2, $department, $account->id, $data['message']['to'], $data['message']['from'], true);
            $chatRoom->externalid = $data['conversation']['id'];
            $chatRoom->save();

            //RC: Gestionamos el cupero del mensaje
            switch ($data['message']['type']) {
                case "text":
                    $body = $data['message']['content']['text'];
                    $subject = substr($body, 0, 100);
                    $message_body_type_id = 1;
                    break;
                case "image":
                    $contents = file_get_contents($data['message']['content']['image']['url']);
                    $name = '/files/' . date('Y') . '/' . date('m') . '/' . substr($data['message']['content']['image']['url'], strrpos($data['message']['content']['image']['url'], '/') + 1);
                    Storage::put($name, $contents);
                    $body = env('APP_URL') . Storage::url($name);
                    $subject = 'Imagen';
                    $message_body_type_id = 2;
                    break;
                case "file":
                    $file_name = $data['message']['content']['file']['caption'];
                    $extension = substr($file_name, strpos($file_name, '.') + 1);
                    $contents = file_get_contents($data['message']['content']['file']['url']);
                    $name = '/files/' . date('Y') . '/' . date('m') . '/' . substr($data['message']['content']['file']['url'], strrpos($data['message']['content']['file']['url'], '/') + 1) . '.' . $extension;
                    Storage::put($name, $contents);
                    $body = env('APP_URL') . Storage::url($name);
                    $subject = $data['message']['content']['file']['caption'];
                    $message_body_type_id = 3;
                    break;
                case "location":
                    $body = $data['message']['content']['location']['latitude'] . ',' . $data['message']['content']['location']['longitude'];
                    $subject = 'Ubicación';
                    $message_body_type_id = 4;
                    break;
                case "audio":
                    $contents = file_get_contents($data['message']['content']['audio']['url']);
                    $name = '/files/' . date('Y') . '/' . date('m') . '/' . substr($data['message']['content']['audio']['url'], strrpos($data['message']['content']['audio']['url'], '/') + 1);
                    Storage::put($name, $contents);
                    $body = env('APP_URL') . Storage::url($name);
                    $subject = 'Audio';
                    $message_body_type_id = 5;
                    break;
                case "video":
                    $contents = file_get_contents($data['message']['content']['video']['url']);
                    $name = '/files/' . date('Y') . '/' . date('m') . '/' . substr($data['message']['content']['video']['url'], strrpos($data['message']['content']['video']['url'], '/') + 1);
                    Storage::put($name, $contents);
                    $body = env('APP_URL') . Storage::url($name);
                    $subject = 'Audio';
                    $message_body_type_id = 6;
                    break;
            }

            //RC: guardar mensaje
            $message = new Message();
            $message->chat_room_id = $chatRoom->id;
            $message->message_type_id = $chatRoom->message_type_id;
            $message->account_id = $account->id;
            $message->externalid = $data['conversation']['id'];
            $message->from = $data['message']['from'];
            $message->to = $data['message']['to'];
            $message->fromName = $account->name;
            $message->toName = '';
            $message->subject = $subject;
            $message->save();

            $message_body = new MessageBody();
            $message_body->message_id = $message->id;
            $message_body->message_body_type_id = $message_body_type_id;
            $message_body->content = $body;
            $message_body->save();

            //RC: aumentamos el unread de los otros usaurios
            DB::update("UPDATE user_chat_room SET unread = unread + 1 WHERE chat_room_id = " . $chatRoom->id);
            DB::update("UPDATE chat_rooms SET updated_at = NOW() WHERE id = " . $chatRoom->id);

            //RC: generamos el evento al canal pertinente
            if ($chatRoom->departments()->count() > 0) {
                foreach ($chatRoom->departments as $department) {
                    broadcast(new NewMessage($message->load('message_body', 'write_user', 'read_user', 'account'), 'App.Department.' . $department->id));
                }
            } else if ($chatRoom->users()->count() > 0) {
                foreach ($chatRoom->users as $user) {
                    broadcast(new NewMessage($message->load('message_body', 'write_user', 'read_user', 'account'), 'App.User.' . $user->id));
                }
            }

            //RC: Procesamos los hooks despues del proceso del mensaje
            $hooks = Hook::where('company_id', $company->id)
                ->where('key', 'AFTER_RECEIVE_WHATSAPP_MESSAGE')
                ->where('active', 1)
                ->orderBy('position', 'asc')
                ->get();

            foreach ($hooks as $hook) {
                try {
                    eval($hook->code);
                } catch (Exception $e) {
                }
            }
        }
    }

    public function receive_whatsapp_messagebird(Request $request) {
        //RC: TEXT SAMPLE 
        $request = json_decode('{"contact":{"attributes":[],"createdDatetime":"2020-06-30T16:55:41Z","customDetails":[],"displayName":"34690729978","firstName":null,"href":"https:\/\/contacts.messagebird.com\/v2\/contacts\/c4262f1054c249be971a756fd549ff00","id":"c4262f1054c249be971a756fd549ff00","lastName":null,"msisdn":34690729978,"updatedDatetime":"2020-06-30T17:16:34Z"},"conversation":{"contactId":"c4262f1054c249be971a756fd549ff00","createdDatetime":"2020-06-30T16:55:41Z","id":"0ea8f34d586544babaecc59bb5afcc1d","lastReceivedDatetime":"2020-07-01T10:20:26.066339206Z","status":"active","updatedDatetime":"2020-06-30T20:05:06.250036381Z"},"message":{"channelId":"09c3a21d853b4bdbaa1a605ead17c989","content":{"text":"Roger"},"conversationId":"0ea8f34d586544babaecc59bb5afcc1d","createdDatetime":"2020-07-01T10:20:25Z","direction":"received","from":"+34690729978","id":"48e710fef9d6453f80fcaaed88189e72","platform":"whatsapp","status":"received","to":"+34684412339","type":"text","updatedDatetime":"2020-07-01T10:20:26.072578638Z"},"type":"message.created"}');
        //RC: IMAGE SAMPLE $request = json_decode('{"contact":{"attributes":[],"createdDatetime":"2020-06-30T16:55:41Z","customDetails":[],"displayName":"34690729978","firstName":null,"href":"https:\/\/contacts.messagebird.com\/v2\/contacts\/c4262f1054c249be971a756fd549ff00","id":"c4262f1054c249be971a756fd549ff00","lastName":null,"msisdn":34690729978,"updatedDatetime":"2020-06-30T17:16:34Z"},"conversation":{"contactId":"c4262f1054c249be971a756fd549ff00","createdDatetime":"2020-06-30T16:55:41Z","id":"0ea8f34d586544babaecc59bb5afcc1d","lastReceivedDatetime":"2020-07-01T10:25:39.780131701Z","status":"active","updatedDatetime":"2020-07-01T10:20:26.072578638Z"},"message":{"channelId":"09c3a21d853b4bdbaa1a605ead17c989","content":{"image":{"url":"https:\/\/media.messagebird.com\/v1\/media\/33352fb4-bae4-4a0e-987e-636e6c195784"}},"conversationId":"0ea8f34d586544babaecc59bb5afcc1d","createdDatetime":"2020-07-01T10:25:36Z","direction":"received","from":"+34690729978","id":"4fa0cd0330984108a3ac1ba97e4e2d74","platform":"whatsapp","status":"received","to":"+34935586520","type":"image","updatedDatetime":"2020-07-01T10:25:39.787681098Z"},"type":"message.created"}');
        //RC: FICHERO SAMPLE $request = json_decode('{"contact-":{"attributes":[],"createdDatetime":"2020-06-30T16:55:41Z","customDetails":[],"displayName":"34690729978","firstName":null,"href":"https:\/\/contacts.messagebird.com\/v2\/contacts\/c4262f1054c249be971a756fd549ff00","id":"c4262f1054c249be971a756fd549ff00","lastName":null,"msisdn":34690729978,"updatedDatetime":"2020-06-30T17:16:34Z"},"conversation":{"contactId":"c4262f1054c249be971a756fd549ff00","createdDatetime":"2020-06-30T16:55:41Z","id":"0ea8f34d586544babaecc59bb5afcc1d","lastReceivedDatetime":"2020-07-01T10:25:39.731446326Z","status":"active","updatedDatetime":"2020-07-01T10:20:26.072578638Z"},"message":{"channelId":"09c3a21d853b4bdbaa1a605ead17c989","content":{"file":{"caption":"02-Wifi-Intro.pdf","url":"https:\/\/media.messagebird.com\/v1\/media\/db332268-732c-4063-aa30-8dc6e0755490"}},"conversationId":"0ea8f34d586544babaecc59bb5afcc1d","createdDatetime":"2020-07-01T10:25:37Z","direction":"received","from":"+34690729978","id":"6562fb3c4ae84a8a88c96af315168ca2","platform":"whatsapp","status":"received","to":"+34935586520","type":"file","updatedDatetime":"2020-07-01T10:25:39.739557626Z"},"type":"message.created"}');
        //RC: LOCATION SAMPLE $request = json_decode('{"contact":{"attributes":[],"createdDatetime":"2020-06-30T16:55:41Z","customDetails":[],"displayName":"34690729978","firstName":null,"href":"https:\/\/contacts.messagebird.com\/v2\/contacts\/c4262f1054c249be971a756fd549ff00","id":"c4262f1054c249be971a756fd549ff00","lastName":null,"msisdn":34690729978,"updatedDatetime":"2020-06-30T17:16:34Z"},"conversation":{"contactId":"c4262f1054c249be971a756fd549ff00","createdDatetime":"2020-06-30T16:55:41Z","id":"0ea8f34d586544babaecc59bb5afcc1d","lastReceivedDatetime":"2020-07-01T10:25:57.189617393Z","status":"active","updatedDatetime":"2020-07-01T10:25:39.739557626Z"},"message":{"channelId":"09c3a21d853b4bdbaa1a605ead17c989","content":{"location":{"latitude":41.376408,"longitude":2.129763}},"conversationId":"0ea8f34d586544babaecc59bb5afcc1d","createdDatetime":"2020-07-01T10:25:56Z","direction":"received","from":"+34690729978","id":"cdd635e7408d4bb6ae90b6eec3c50230","platform":"whatsapp","status":"received","to":"+34935586520","type":"location","updatedDatetime":"2020-07-01T10:25:57.198799756Z"},"type":"message.created"}');
        //RC: AUDIO SAMPLE $request = json_decode('{"contact":{"attributes":[],"createdDatetime":"2020-06-30T16:55:41Z","customDetails":[],"displayName":"34690729978","firstName":null,"href":"https:\/\/contacts.messagebird.com\/v2\/contacts\/c4262f1054c249be971a756fd549ff00","id":"c4262f1054c249be971a756fd549ff00","lastName":null,"msisdn":34690729978,"updatedDatetime":"2020-06-30T17:16:34Z"},"conversation":{"contactId":"c4262f1054c249be971a756fd549ff00","createdDatetime":"2020-06-30T16:55:41Z","id":"0ea8f34d586544babaecc59bb5afcc1d","lastReceivedDatetime":"2020-07-01T10:26:04.747877799Z","status":"active","updatedDatetime":"2020-07-01T10:25:57.198799756Z"},"message":{"channelId":"09c3a21d853b4bdbaa1a605ead17c989","content":{"audio":{"url":"https:\/\/media.messagebird.com\/v1\/media\/023bd171-5c72-4ae4-a82f-6ee282901bf4"}},"conversationId":"0ea8f34d586544babaecc59bb5afcc1d","createdDatetime":"2020-07-01T10:26:03Z","direction":"received","from":"+34690729978","id":"df68095f04b54250834ac6fca0b3d9ca","platform":"whatsapp","status":"received","to":"+34935586520","type":"audio","updatedDatetime":"2020-07-01T10:26:04.753877143Z"},"type":"message.created"}');
        //RC: VIDEO SAMPLE $request = json_decode('{"contact":{"attributes":[],"createdDatetime":"2020-06-30T16:55:41Z","customDetails":[],"displayName":"34690729978","firstName":null,"href":"https:\/\/contacts.messagebird.com\/v2\/contacts\/c4262f1054c249be971a756fd549ff00","id":"c4262f1054c249be971a756fd549ff00","lastName":null,"msisdn":34690729978,"updatedDatetime":"2020-06-30T17:16:34Z"},"conversation":{"contactId":"c4262f1054c249be971a756fd549ff00","createdDatetime":"2020-06-30T16:55:41Z","id":"0ea8f34d586544babaecc59bb5afcc1d","lastReceivedDatetime":"2020-07-01T10:27:21.828073102Z","status":"active","updatedDatetime":"2020-07-01T10:26:55.317396852Z"},"message":{"channelId":"09c3a21d853b4bdbaa1a605ead17c989","content":{"video":{"url":"https:\/\/media.messagebird.com\/v1\/media\/6aa401c5-b765-494b-ab48-ef4fd376ec2f"}},"conversationId":"0ea8f34d586544babaecc59bb5afcc1d","createdDatetime":"2020-07-01T10:27:19Z","direction":"received","from":"+34690729978","id":"33c99b08cc26454ab18ee336829b35d3","platform":"whatsapp","status":"received","to":"+34935586520","type":"video","updatedDatetime":"2020-07-01T10:27:21.835618866Z"},"type":"message.created"}');

        $name = 'whatstapp/'.date('Y').'/'.date('m').'/'.date('d').'_log.txt';
        $data = $request->all();
        Storage::append($name, json_encode($data));
        //RC: obtener la empresa y el departamento
        $company = self::get_company_from_whatsapp_number($data['message']['to']);

        if(!empty($company)) {
            //RC: obtener el departamento
            $department = self::get_department_from_whatsapp_number($data['message']['from'], $data['message']['to'], $company->id);
            
            //RC: obtener el contacto, en caso de no existir lo generamos
            $contact = get_contact_by_contact_type($data['message']['from'], 1, false);
            if(empty($contact)) {
                $create_contact = true;
                $contact = get_contact_by_contact_type($data['message']['from'], 1, true);
            } else {
                $create_contact = false;
            }

            //RC: Funciones previas a guardar el mensajes
            $hooks = Hook::where('company_id', $company->id)
                ->where('key', 'BEFORE_WHATSAPP_MESSAGE')
                ->where('active', 1)
                ->orderBy('position', 'asc')
                ->get();

            foreach($hooks as $hook) {
                try {
                    eval($hook->code);
                } catch (Exception $e) {
                    
                }
            }

            if(!empty($department_hook)) {
                $department = $department_hook ;
            }

            //RC: obtener el chat_room
            if(!empty($department)) {
                $department_id = $department->id;
            } else {
                $department_id = null;
            }

            $chat_room = get_chat_room_by_department_contact(2, $department_id, $contact->id, true);
            $chat_room->externalid = $data['conversation']['id'];
            $chat_room->save();
            
            //RC: Gestionamos el cupero del mensaje
            switch($data['message']['type']) {
                case "text":
                    $body = $data['message']['content']['text'];
                    $subject = substr($body, 0, 100);
                    $message_body_type_id = 1;
                break;
                case "image":
                    $contents = file_get_contents($data['message']['content']['image']['url']);
                    $name = '/files/'.date('Y').'/'.date('m').'/'.substr($data['message']['content']['image']['url'], strrpos($data['message']['content']['image']['url'], '/') + 1);
                    Storage::put($name, $contents);
                    $body = env('APP_URL').Storage::url($name);
                    $subject = 'Imagen';
                    $message_body_type_id = 2;
                break;
                case "file":
                    $file_name = $data['message']['content']['file']['caption'];
                    $extension = substr($file_name, strpos($file_name, '.') + 1);
                    $contents = file_get_contents($data['message']['content']['file']['url']);
                    $name = '/files/'.date('Y').'/'.date('m').'/'.substr($data['message']['content']['file']['url'], strrpos($data['message']['content']['file']['url'], '/') + 1).'.'.$extension;
                    Storage::put($name, $contents);
                    $body = env('APP_URL').Storage::url($name);
                    $subject = $data['message']['content']['file']['caption'];
                    $message_body_type_id = 3;
                break;
                case "location":
                    $body = $data['message']['content']['location']['latitude'].','.$data['message']['content']['location']['longitude'];
                    $subject = 'Ubicación';
                    $message_body_type_id = 4;
                break;
                case "audio":
                    $contents = file_get_contents($data['message']['content']['audio']['url']);
                    $name = '/files/'.date('Y').'/'.date('m').'/'.substr($data['message']['content']['audio']['url'], strrpos($data['message']['content']['audio']['url'], '/') + 1);
                    Storage::put($name, $contents);
                    $body = env('APP_URL').Storage::url($name);
                    $subject = 'Audio';
                    $message_body_type_id = 5;
                break;
                case "video":
                    $contents = file_get_contents($data['message']['content']['video']['url']);
                    $name = '/files/'.date('Y').'/'.date('m').'/'.substr($data['message']['content']['video']['url'], strrpos($data['message']['content']['video']['url'], '/') + 1);
                    Storage::put($name, $contents);
                    $body = env('APP_URL').Storage::url($name);
                    $subject = 'Audio';
                    $message_body_type_id = 6;
                break;
            }
            
            //RC: guardar mensaje
            $message = new Message();
            $message->chat_room_id = $chat_room->id;
            $message->message_type_id = $chat_room->message_type_id;
            $message->contact_id = $contact->id;
            if(!empty($contact->account_id)) {
                $message->account_id = $contact->account_id;
            }
            $message->externalid = $data['conversation']['id'];
            $message->from = $data['message']['from'];
            $message->to = $data['message']['to'];
            $message->fromName = $contact->name;
            $message->toName = '';
            $message->subject = $subject;
            $message->save();

            $message_body = new MessageBody();
            $message_body->message_id = $message->id;
            $message_body->message_body_type_id = $message_body_type_id;
            $message_body->content = $body;
            $message_body->save();

            //RC: aumentamos el unread de los otros usaurios
            DB::update("UPDATE user_chat_room SET unread = unread + 1 WHERE chat_room_id = ".$chat_room->id);

            //RC: generamos el evento al canal pertinente
            if($chat_room->departments->count() > 0) {
                foreach($chat_room->departments as $department) {
                    broadcast(new NewMessage($message->load('message_body', 'write_user', 'read_user', 'account', 'contact'), 'App.Department.'.$department->id));
                }
            }

            //RC: Funciones posterior a guardar el mensajes
            $hooks = Hook::where('company_id', $company->id)
                ->where('key', 'AFTER_WHATSAPP_MESSAGE')
                ->where('active', 1)
                ->orderBy('position', 'asc')
                ->get();

            foreach($hooks as $hook) {
                try {
                    eval($hook->code);
                } catch (Exception $e) {
                }
            }
        } else {
            //RC: No tenemos el número registrado, lo descartamos
        }
    }

    /**
     * Devuelve el identificador de la organización que tiene el número de teléfono asignado
     * En caso de no tener número devuelve null
     * @param Number $number Número de teléfono
     * @return Number identificar de la compañía
     */
    private function get_company_from_whatsapp_number($number) {
        $company_config = CompanyConfig::where('key', 'whatsapp_numbers')
            ->where('value', 'like', '%'.$number.'%')
            ->first();

        if(!empty($company_config)) {
            $company = Company::findOrFail($company_config->company_id);

        } else {
            $company = null;
        }

        return $company;
    }

    /**
     * Devuelve el identificador del departamento que tiene el número de teléfono asignado
     * En caso de no tener número devuelve null o en caso de que no sea de la misma organización
     * @param Number $number Número de teléfono
     * @param Number $company_id Identificador de la compañía
     * @return Number identificar del departamento
     */
    private function get_department_from_whatsapp_number($from, $to, $company_id) {
        $department_config = DepartmentConfig::where('key', 'whatsapp_numbers')
            ->where('value', 'like', '%'.$to.'%')
            ->first();

        if(!empty($department_config)) {
            if($department_config->department->company_id == $company_id) {
                $department = Department::findOrFail($department_config->department_id);
            } else {
                $department = null;
            }

        } else {
            $message = Message::where('from', $from)
                ->where('message_type_id', 2)
                ->where('to', $to)
                ->orderBy('id', 'DESC')
                ->first();

            if(!empty($message)) {
                $department = $message->chat_room->departments()->first();
            } else {
                $department = null;
            }
        }

        return $department;
    }

    public function receive_whatsapp(Request $request) {
        // TEXTO $request = json_decode('{"SmsMessageSid":"SM7bdc9bab1f4a88e31babaa68cd3e956e","NumMedia":"0","SmsSid":"SM7bdc9bab1f4a88e31babaa68cd3e956e","SmsStatus":"received","Body":"Testeando 2","To":"whatsapp:+34931070740","NumSegments":"1","MessageSid":"SM7bdc9bab1f4a88e31babaa68cd3e956e","AccountSid":"ACc17a78d6d2be0ada1dc6f1c72d01e192","From":"whatsapp:+34690729978","ApiVersion":"2010-04-01"}');
        // IMAGEN $request = json_decode('{"MediaContentType0":"image\/jpeg","SmsMessageSid":"MMda1d3a8adbbaaf05dfd4b3f02b55054e","NumMedia":"1","SmsSid":"MMda1d3a8adbbaaf05dfd4b3f02b55054e","SmsStatus":"received","Body":null,"To":"whatsapp:+34931070740","NumSegments":"1","MessageSid":"MMda1d3a8adbbaaf05dfd4b3f02b55054e","AccountSid":"ACc17a78d6d2be0ada1dc6f1c72d01e192","From":"whatsapp:+34690729978","MediaUrl0":"https:\/\/api.twilio.com\/2010-04-01\/Accounts\/ACc17a78d6d2be0ada1dc6f1c72d01e192\/Messages\/MMda1d3a8adbbaaf05dfd4b3f02b55054e\/Media\/ME20f3cd12dcde23d0249e92741674ad42","ApiVersion":"2010-04-01"}');
        $department_id = 1; //RC: marcamos la forma de contacto del chat del glovo

        //RC: Miramos si tenemos un código de departamento
        $department_id = self::getDepartmentMessage($request->all());

        if(empty($department_id)) {
            //RC: Si no tenemos departamento tenemos que responder que no puede 
            $to_phone = str_replace('whatsapp:', '', $request->From);
            $from_phone = str_replace('whatsapp:', '', $request->To);
            $msg = 'Para poder comunicarse con un establecimiento debe utilizar la aplicación de https://econecta.netlu.dev de Salvador Escoda';
            self::send_whatsapp($from_phone, $to_phone, $msg);

        } else {
            $department = Department::findOrFail($department_id);
            $from_phone = str_replace('whatsapp:', '', $request->From);
            $to_phone = str_replace('whatsapp:', '', $request->To);
            if($request->NumMedia == 0) {
                $message_body_type_id = 1;
                $body = $request->Body;
            } elseif(strpos($request->MediaContentType0, 'image') !== FALSE) {
                $message_body_type_id = 2;
                $body = $request->MediaUrl0;
            } else {
                $message_body_type_id = 3;
                $body = $request->MediaUrl0;
            }

            //RC: mirar si tenim el contacte i si no el tenim el creem
            $contact = get_contact_by_contact_type($from_phone, 1, false);
            if(empty($contact)) {
                $contact = get_contact_by_contact_type($from_phone, 1, true);
            } else {
                if (strpos($contact->name, '##AUTO##') !== FALSE) {
                    $old_name = $contact->name;
                    $contact->name = $body;
                    $contact->save();

                    DB::update('UPDATE chat_rooms SET name = "' . $contact->name . '" WHERE name = "' . $old_name . '"');
                    DB::update('UPDATE user_chat_room SET name = "' . $contact->name . '" WHERE name = "' . $old_name . '"');
                }
            }

            if(!empty($this->account_id)) {
                $contact->account_id = $this->account_id;
                $contact->save();

                $token = SeAccountToken::where('id', $this->token_id)->first();
                if(!empty($token)) {
                    $token->contact_id = $contact->id;
                    $token->save();
                }
            }

            //RC: mirar si tenim el chat i si no el tenim el creem
            $chat_room = get_chat_room_by_department_contact(2, $department_id, $contact->id, true);

            if(!empty($this->account_id)) {
                //RC: modificamos el nombre
                DB::update("
                    UPDATE contact_chat_room
                    INNER JOIN chat_rooms ON chat_rooms.id = contact_chat_room.chat_room_id 
                    INNER JOIN user_chat_room ON chat_rooms.id = user_chat_room.chat_room_id 
                    SET contact_chat_room.name= '".$contact->name." (".$contact->account->code." - ".$contact->account->name.")', user_chat_room.name= '".$contact->name." (".$contact->account->code." - ".$contact->account->name.")'
                    WHERE contact_id = ".$contact->id
                );
            }

            //RC: registrem el missatge
            $message = new Message();
            $message->chat_room_id = $chat_room->id;
            $message->message_type_id = $chat_room->message_type_id;
            $message->contact_id = $contact->id;
            $message->from = $from_phone;
            $message->fromName = $contact->name;
            $message->to = $to_phone;
            $message->toName = $department->name;
            switch ($message_body_type_id) {
                case 1:
                    if(strlen(strip_tags($body)) <= 100) {
                        $message->subject = substr(strip_tags($body), 0, 100);
                    } else {
                        $message->subject = substr(strip_tags($body), 0, 97).'...';
                    }
                    break;
                case 2:
                    $message->subject = 'Imagen';
                    break;
                case 3:
                    $message->subject = 'Fichero';
                    break;
            }
            $message->save();

            //RC: Guardamos el contenido del mensaje
            $message_body = new MessageBody();
            $message_body->message_id = $message->id;
            $message_body->message_body_type_id = $message_body_type_id;
            switch ($message_body->message_body_type_id) {
                case 1:
                    if(filter_var($body, FILTER_VALIDATE_URL)) {
                        $message_body->content = '<a href="'.$body.'" target="_blank">'.$body.'</a>';
                    } else {
                        $message_body->content = nl2br($body);
                    }
                    break;
                case 2:
                    $message_body->content = '<a href="'.$body.'" target="_blank"><figure class="image"><img src="'.$body.'" class="is-fullwidth"/></figure></a>';
                    break;
                case 3:
                    $message_body->content = '<a href="'.$body.'" target="_blank">Ver fichero</a>';
                    break;
            }
            $message_body->save();

            //RC: generamos el evento al canal pertinente
            if($chat_room->departments->count() > 0) {
                foreach($chat_room->departments as $department) {
                    broadcast(new NewMessage($message->load('message_body', 'write_user', 'read_user', 'account', 'contact'), 'App.Department.'.$department->id));
                }
            }

            //RC: aumentamos el unread de los otros usaurios
            DB::update("UPDATE user_chat_room SET unread = unread + 1 WHERE chat_room_id = ".$chat_room->id);

            //RC: Miramos si tenemos que notificar el departamento
            if($this->report_department) {
                //RC: obtenemos el departamento
                $department = $chat_room->departments()->first();

                if(!empty($department) && $department->code != 'GL') {
                    $msg = 'Usted está hablando con el establecimiento '.$department->name;

                    self::send_message($chat_room->id, 1, $message_body->message_body_type_id, $msg, false);
                }
            }

            //RC: si tenim que demanar el nom el demanem
            if(strpos($contact->name, '##AUTO##') !== FALSE) {
                self::send_message($message->chat_room_id, 1, 1, 'Indicar el nombre', false);
            } else {
                if($chat_room->departments->count() > 0) {
                    foreach($chat_room->departments as $department) {
                        $sessions = $department->users()->join('user_sessions', 'user_sessions.user_id', '=', 'users.id')
                            ->whereNull('user_sessions.end')
                            ->count();
                            
                        if($sessions == 0) {
                            $msg = 'No hay ningún usuario conectado en '.$department->name;
                            self::send_message($chat_room->id, 1, $message_body->message_body_type_id, $msg, false);
                        }
                    }
                }
            }
        }
    }

    private function getDepartmentMessage($message_info) {
        if($message_info['NumMedia'] == 0) {
            //RC: Si tenemos un texto miramos si es un código
            $body = $message_info['Body'];
            $parts = explode(' ', $body);
            if(!empty($parts)) {
                $subparts = explode('-', $parts[0]);
                $code = $subparts[0];
                if(!empty($subparts[1])) {
                    $token_id = $subparts[1];

                    $token = SeAccountToken::where('id', $token_id)
                        ->first();

                    if(!empty($token)) {
                        $this->token_id = $token_id;
                        $this->account_id = $token->account_id;
                    }
                }
                $department = Department::where('code', $code)
                    ->first();

                if(!empty($department)) {
                    $department_id = $department->id;

                    //RC: tenemos que notificar que cambiamos de departamento
                    $this->report_department = true;
                }
            }
        }

        //RC: Si no tenemos un departamento
        if(empty($department_id)) {
            //RC: Buscamos el último mensaje del número
            $message = Message::where('message_type_id', 2)
                ->where('from', str_replace('whatsapp:', '', $message_info['From']))
                ->orderBy('id', 'desc')
                ->first();

            if(!empty($message)) {
                $department_id = $message->chat_room->departments()->first()->id;
            } else {
                $department_id = null;
            }
        }

        return $department_id;
    }

    private function send_whatsapp($from, $to, $body) {
        /* $sid    = "ACc17a78d6d2be0ada1dc6f1c72d01e192";
        $token  = "333dc2881ec62c0be2e2b1932fc0d429";
        $twilio = new Client($sid, $token);

        $message = $twilio->messages
            ->create("whatsapp:$to", // to
                array(
                    "from" => "whatsapp:$from",
                    "body" => strip_tags($body)
                )
            );

        return $message; */
    }

    private function send_whatsapp_messagebird($from, $to, $body) {
        //RC: buscamos el indice del array
        $exist_number = false;
        $config = CompanyConfig::where('key', 'whatsapp_numbers')
                ->where('value', 'like', '%'.$from.'%')
                ->first();
            
        if (!empty($config)) {
            $exist_number = true;
            $numbers = explode(',', $config->value);

            $config2 = CompanyConfig::where('company_id', $config->company_id)
                ->where('key', 'whatsapp_channels')
                ->first();

            if (!empty($config2)) {
                $channels = explode(',', $config2->value);
            } else {
                $exist_number = false;
            }

            $config3 = CompanyConfig::where('company_id', $config->company_id)
                ->where('key', 'whatsapp_accesskey')
                ->first();

            if (!empty($config3)) {
                $accesskeys = explode(',', $config3->value);
            } else {
                $exist_number = false;
            }

        } else {
            $config = DepartmentConfig::where('key', 'whatsapp_numbers')
                ->where('value', 'like', '%'.$from.'%')
                ->first();

            if(!empty($config)) {
                $exist_number = true;
            }

            $numbers = explode(',', $config->value);

            $config2 = DepartmentConfig::where('company_id', $config->company_id)
                ->where('key', 'whatsapp_channels')
                ->first();

            if (!empty($config2)) {
                $channels = explode(',', $config2->value);
            } else {
                $exist_number = false;
            }

            $config3 = DepartmentConfig::where('company_id', $config->company_id)
                ->where('key', 'whatsapp_accesskey')
                ->first();

            if (!empty($config3)) {
                $accesskeys = explode(',', $config3->value);
            } else {
                $exist_number = false;
            }

        }

        
        if($exist_number) {
            $index = array_search($from, $numbers);
            $channel = $channels[$index];
            $accesskey = $accesskeys[$index];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://conversations.messagebird.com/v1/send",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{ \"to\":\"".$to."\", \"from\":\"".$channel."\", \"type\":\"text\", \"content\":{ \"text\":\"".$body."\" }, \"reportUrl\":\"https://adminconecta.salvadorescoda.com/api/whatsapp/event\" }",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: AccessKey ".$accesskey,
                    "Content-Type: application/json"
                )
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return json_decode($response);

        } else {
            $response = ['id' => 'error'];
            return json_decode($response);
        }
    }

    private function send_template_whatsapp_messagebird($from, $to, $chat_room, $message_template, $params) {
        //RC: buscamos el indice del array
        $exist_number = false;
        $config = CompanyConfig::where('key', 'whatsapp_numbers')
                ->where('value', 'like', '%'.$from.'%')
                ->first();
            
        if (!empty($config)) {
            $exist_number = true;
            $numbers = explode(',', $config->value);

            $config2 = CompanyConfig::where('company_id', $config->company_id)
                ->where('key', 'whatsapp_channels')
                ->first();

            if (!empty($config2)) {
                $channels = explode(',', $config2->value);
            } else {
                $exist_number = false;
            }

            $config3 = CompanyConfig::where('company_id', $config->company_id)
                ->where('key', 'whatsapp_accesskey')
                ->first();

            if (!empty($config3)) {
                $accesskeys = explode(',', $config3->value);
            } else {
                $exist_number = false;
            }

        } else {
            $config = DepartmentConfig::where('key', 'whatsapp_numbers')
                ->where('value', 'like', '%'.$from.'%')
                ->first();

            if(!empty($config)) {
                $exist_number = true;
            }

            $numbers = explode(',', $config->value);

            $config2 = DepartmentConfig::where('company_id', $config->company_id)
                ->where('key', 'whatsapp_channels')
                ->first();

            if (!empty($config2)) {
                $channels = explode(',', $config2->value);
            } else {
                $exist_number = false;
            }

            $config3 = DepartmentConfig::where('company_id', $config->company_id)
                ->where('key', 'whatsapp_accesskey')
                ->first();

            if (!empty($config3)) {
                $accesskeys = explode(',', $config3->value);
            } else {
                $exist_number = false;
            }

        }

        
        if($exist_number) {
            $index = array_search($from, $numbers);
            $channel = $channels[$index];
            $accesskey = $accesskeys[$index];
            $namespace = '925c120f_3768_4952_8629_d3dccdb28397';

            $json_params = [];
            foreach($params as $param) {
                $json_params[] = ["default" => $param];
            }

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "https://conversations.messagebird.com/v1/conversations/".$chat_room->externalid."/messages",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>"{\"type\": \"hsm\",\"content\":{\"hsm\": {\"namespace\": \"".$namespace."\",\"templateName\": \"".$message_template['name']."\",\"language\": {\"policy\": \"deterministic\",\"code\": \"".$message_template['language']."\"},\"params\": ".json_encode($json_params)."}}}",
            CURLOPT_HTTPHEADER => array(
                "Authorization: AccessKey ".$accesskey,
                "Content-Type: application/json"
            ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return json_decode($response);

        } else {
            echo 'no ecncontramos la configuración';exit;
        }
    }

    private function sendMessage(int $chatRoomId, int $userId, int $messageBodyTypeId, string $body, bool $actionsAfterSend = true)
    {
	$namehook = 'whatstapphooks/' . date('Y') . '/' . date('m') . '/' . date('d') . '_log.txt';
        Storage::append($namehook, "entramos");

        //RC: Obtenemos el usuario
        $user = User::findOrFail($userId);

        //RC: Obtenemos el chat
        $chatRoom = ChatRoom::findOrFail($chatRoomId);

        $message = new Message();
        $message->chat_room_id = $chatRoom->id;
        $message->message_type_id = $chatRoom->message_type_id;
        $message->write_user_id = $user->id;
        switch ($chatRoom->message_type_id) {
            case 1:
                //RC: En caso de un chat tenemos que guardar el usuario
                $message->from = $user->username;
                break;
            case 2:
                //RC: En caso de un whatsapp tenemos que guardar el número desde donde lo mandamos
                $message->from = $chatRoom->from;
                break;
        }
        $message->fromName = $user->name;

        //RC: Guardamos el asunto, en función del tipo de mensaje
        switch ($messageBodyTypeId) {
            case 1:
                if (strlen(strip_tags($body)) <= 100) {
                    $message->subject = substr(strip_tags($body), 0, 100);
                } else {
                    $message->subject = substr(strip_tags($body), 0, 97) . '...';
                }
                break;
            case 2:
                $message->subject = 'Imagen';
                break;
            case 3:
                $message->subject = 'Fichero';
                break;
        }

        //RC: Guardamos el destinatario en funcion del tipo de mensaje
        switch ($chatRoom->message_type_id) {
            case 1:
                $user_chat_room = $chatRoom->users()->where('user_id', $user->id)->first();
                $message->to = $user_chat_room->pivot->name;
                $message->toName = $user_chat_room->pivot->name;
                $message->from = $user->username;
                break;
            case 2:
                $message->to = $chatRoom->to;
                $message->toName = $chatRoom->to;
                break;
        }

        //RC: Guardamos el registro
        $message->save();

        //RC: Guardamos el contenido del mensaje
        $message_body = new MessageBody();
        $message_body->message_id = $message->id;
        $message_body->message_body_type_id = $messageBodyTypeId;
        switch ($message_body->message_body_type_id) {
            case 1:
                //RC: Si es un mensaje de texto tenemos que validar si existe una url.
                if (filter_var($body, FILTER_VALIDATE_URL)) {
                    $message_body->content = '<a href="' . $body . '" target="_blank">' . $body . '</a>';
                } else {
                    $message_body->content = nl2br($body);
                }
                break;
            case 2:
                //RC: Si tenemos una imagen guardamos el path de la imagen
                $body_array = explode('|', $body);
                $message_body->content = env('APP_URL') . $body_array[0];
                break;
            case 3:
                //RC: Si tenemos un fichero guardamo el path del fichero
                $body_array = explode('|', $body);
                $message_body->content = env('APP_URL') . $body_array[0];
                break;
        }
        $message_body->save();

        //RC: aumentamos el unread de los otros usaurios
        DB::update("UPDATE user_chat_room SET unread = unread + 1 WHERE chat_room_id = " . $chatRoom->id . " AND user_id <> " . $user->id);
        DB::update("UPDATE chat_rooms SET updated_at = NOW() WHERE id = " . $chatRoom->id);

        //RC: Si es un mensaje de tipo whatsapp lo mandamos
        if ($chatRoom->message_type_id == 2 && !empty($message->to)) {
            self::send_whatsapp_messagebird($message->from, $message->to, $message_body->content);
        }

        //RC: generamos el evento al canal pertinente
        if (
            $chatRoom->departments->count() > 0
        ) {
            foreach ($chatRoom->departments as $department) {
                broadcast(new NewMessage($message->load('message_body', 'write_user', 'read_user', 'account'), 'App.Department.' . $department->id));
            }
        } elseif ($chatRoom->users()->count() > 0) {
            foreach ($chatRoom->users as $user) {
                broadcast(new NewMessage($message->load('message_body', 'write_user', 'read_user', 'account'), 'App.User.' . $user->id));
            }
        }

        if ($actionsAfterSend) {
            //RC: Miramos si tenemos algun departamento sin usuarios
            if ($chatRoom->departments->count() > 0) {
                foreach ($chatRoom->departments as $department) {
                    $sessions = $department->users()->join('user_sessions', 'user_sessions.user_id', '=', 'users.id')
                    ->whereNull('user_sessions.end')
                    ->count();

                    if ($sessions == 0) {
                        $messageBody = 'No hay ningún usuario conectado en ' . $department->name;
                        self::send_message($chatRoomId, 1, 1, $messageBody, false);
                    }
                }
            }
        }

        return $message->load(
            'message_body',
            'write_user',
            'read_user',
            'account'
        );
    }

    private function sendMessageTemplate($chat_room_id, $user_id, $message_template, $params, $actions_after_send = true)
    {
        //RC: Obtenemos el usuario que manda el mensaje
        $user = User::findOrFail($user_id);

        //RC: Generamos el string_message
        $i = 1;
        $string_message = $message_template['message'];
        foreach($params as $param) {
            $string_message = str_replace('{{'.$i.'}}', $param, $string_message);
            $i++;
        }

        //RC: Obtenemos la sala de chat
        $chat_room = ChatRoom::findOrFail($chat_room_id);
        
        $message = new Message();
        $message->chat_room_id = $chat_room->id;
        $message->message_type_id = $chat_room->message_type_id;
        $message->write_user_id = $user->id;
        switch ($chat_room->message_type_id) {
            case 1:
                //RC: En caso de un chat tenemos que guardar el usuario
                $message->from = $user->username;
                break;
            case 2:
                //RC: En caso de un whatsapp tenemos que guardar el número desde donde lo mandamos
                $message->from = $chat_room->from;
                break;
        }
        $message->fromName = $user->name;
        $message->subject = substr(strip_tags($string_message), 0, 100);
        

        //RC: Guardamos el destinatario en funcion del tipo de mensaje
        switch ($chat_room->message_type_id) {
            case 1:
                $user_chat_room = $chat_room->users()->where('user_id', $user->id)->first();
                $message->to = $user_chat_room->pivot->name;
                $message->toName = $user_chat_room->pivot->name;$message->from = $user->username;
                break;
            case 2:
                if(!empty($chat_room->contacts()->count())) {
                    $contact = $chat_room->contacts[0];
                    if(!empty($contact->phones()->count())) {
                        $phone = $contact->phones[0];
                        $message->to = $phone->value;
                        $message->toName = $contact->name;
                    } else {
                        $message->to = '';
                        $message->toName = $contact->name;
                    }
                } else {
                    $message->to = '';
                    $message->toName = '';
                }
                break;
        }

        //RC: Guardamos el registro
        $message->save();

        //RC: Guardamos el contenido del mensaje
        $message_body = new MessageBody();
        $message_body->message_id = $message->id;
        $message_body->message_body_type_id = 1;
        $message_body->content = nl2br($string_message);
        $message_body->save();

        //RC: aumentamos el unread de los otros usaurios
        DB::update("UPDATE user_chat_room SET unread = unread + 1 WHERE chat_room_id = ".$chat_room->id." AND user_id <> ".$user->id);
        DB::update("UPDATE chat_rooms SET updated_at = NOW() WHERE id = " . $chat_room->id);

        //RC: Si es un mensaje de tipo whatsapp lo mandamos
        if($chat_room->message_type_id == 2 && !empty($message->to)) {

            $limit_date = date('Y-m-d H:i:s', strtotime("-24 hours"));
            $last_message = Message::where('chat_room_id', $chat_room->id)
                ->whereNull('write_user_id')
                ->where('created_at', '>', $limit_date)
                ->count();
            
            if(!empty($last_message)) {
                $response = self::send_whatsapp_messagebird($message->from, $message->to, $message_body->content);
                $message->externalid = $response->id;
            } else {
                $response = self::send_template_whatsapp_messagebird($message->from, $message->to, $chat_room, $message_template, $params);
                $message->externalid = $response->id;
            }
        }

        //RC: generamos el evento al canal pertinente
        if($chat_room->departments->count() > 0) {
            foreach($chat_room->departments as $department) {
                broadcast(new NewMessage($message->load('message_body', 'write_user', 'read_user', 'account'), 'App.Department.' . $department->id));
            }
        } elseif($chat_room->users()->count() > 0) {
            foreach($chat_room->users as $user) {
                broadcast(new NewMessage($message->load('message_body', 'write_user', 'read_user', 'account'), 'App.User.' . $user->id));
            }
        }

        if($actions_after_send) {
            //RC: Miramos si tenemos algun departamento sin usuarios
            /*if($chat_room->departments->count() > 0) {
                foreach($chat_room->departments as $department) {
                    $sessions = $department->users()->join('user_sessions', 'user_sessions.user_id', '=', 'users.id')
                        ->whereNull('user_sessions.end')
                        ->count();
                        
                    if($sessions == 0) {
                        $message_body = 'No hay ningún usuario conectado en '.$department->name;
                        self::sendMessage($chat_room_id, 1, $message_body_type_id, $message_body, false);
                    }
                }
            }*/
        }

        return $message->load('message_body', 'write_user', 'read_user', 'account');
    }
}
