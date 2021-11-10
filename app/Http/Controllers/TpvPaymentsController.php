<?php

namespace App\Http\Controllers;

use App\ChatRoom;
use App\Events\NewMessage;
use App\Message;
use App\MessageBody;
use App\TpvPayment;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TpvPaymentsController extends Controller
{
    public function resume($customer_number, $code) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://ews01.salvadorescoda.com/api/accounts/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => array('username' => 'conecta', 'password' => 'escoda00')
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $json = json_decode($response);
        if (!empty($json->token)) {
            $curl2 = curl_init();
            curl_setopt_array($curl2, array(
                CURLOPT_URL => "https://ews01.salvadorescoda.com/api/execute/q_cab_ped_vta",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => array('token' => $json->token, 'accountid' => '4', 'order_number' => $code)
            ));
            $response2 = curl_exec($curl2);
            curl_close($curl2);
            $json2 = json_decode($response2);
            if (!$json2->error && !empty($json2->data[0]->HEADER_ID)) {
                if($customer_number != $json2->data[0]->CUSTOMER_NUMBER) {
                    abort(403);
                }

                $curl3 = curl_init();
                curl_setopt_array($curl3, array(
                    CURLOPT_URL => "https://ews01.salvadorescoda.com/api/execute/q_lin_ped_vta",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => array('token' => $json->token, 'accountid' => '4', 'header_id' => $json2->data[0]->HEADER_ID)
                ));
                $response3 = curl_exec($curl3);
                curl_close($curl3);
                $json3 = json_decode($response3);

                if (!$json3->error) {
                    $order['id'] = $json2->data[0]->HEADER_ID;
                    $order['code'] = $code;
                    $order['date'] = $json2->data[0]->ORDERED_DATE;
                    $order['customer_number'] = $json2->data[0]->CUSTOMER_NUMBER;
                    $order['name'] = $json2->data[0]->CUSTOMER_NAME;
                    $order['base'] = $json2->data[0]->BASE;
                    $order['carrier_price'] = $json2->data[0]->CARGOS;
                    $order['tax'] = $json2->data[0]->IMPUESTO;
                    $order['total'] = $json2->data[0]->TOTAL;
                    $order['total_to_pay'] = $json2->data[0]->PENDIENTE;

                    $i = 0;
                    foreach($json3->data as $line) {
                        $order['lines'][$i]['number'] = $line->LINE_NUMBER;
                        $order['lines'][$i]['code'] = $line->ORDERED_ITEM;
                        $order['lines'][$i]['name'] = $line->DESCRIPTION;
                        $order['lines'][$i]['quantity'] = $line->ORDERED_QUANTITY;
                        $order['lines'][$i]['units'] = $line->ORDER_QUANTITY_UOM;
                        $order['lines'][$i]['unit_price'] = $line->UNIT_SELLING_PRICE_PER_PQTY;
                        $order['lines'][$i]['price'] = $line->EXTENDED_PRICE;
                        $order['lines'][$i]['comments'] = $line->OBSERVACIONES;
                        $i++;
                    }

                    $tpv_payment = new TpvPayment();
                    $tpv_payment->company_id = 1;
                    $tpv_payment->header_id = $order['id'];
                    $tpv_payment->code = $code;
                    $tpv_payment->customer_code = $customer_number;
                    $tpv_payment->price = $order['total_to_pay'];
                    $tpv_payment->save();
                }

            } else {
                abort(403);
            }
        } else {
            abort(401);
        }

        return view('tpv_payments.resume', [
            'order' => $order,
            'tpv_payment' => $tpv_payment,
        ]);
    }

    public function do_payment(Request $request, $id) {
        if(!empty($request->id) && !empty($request->code) && !empty($request->price)) {
        //if(true) {
            $tpv_payment = TpvPayment::findOrFail($id);
            if($request->code == $tpv_payment->code && $request->price == $tpv_payment->price) {
            //if(true) {
                $tpv_payment->request_code = date('ymdHis');
                $tpv_payment->save();

                $clave = env('REDSYS_KEY');
                $name = env('REDSYS_NAME');
                $code = env('REDSYS_CODE');
                $terminal = env('REDSYS_TERMINAL');
                $num_tpv = $tpv_payment->request_code;
                $amount = round($tpv_payment->price * 100);
                $currency = env('REDSYS_CURRENCY');
                $transactionType = '0';
                $urlMerchant = 'http://' . $_SERVER['SERVER_NAME'];
                $product = 'Pedido ' . $tpv_payment->code;
                $urlOK = 'https://' . $_SERVER['SERVER_NAME'] . '/tpv_payments/ok_payment/' . $tpv_payment->id;
                $urlKO = 'https://' . $_SERVER['SERVER_NAME'] . '/tpv_payments/ko_payment/' . $tpv_payment->id;

                return view('tpv_payments.do_payment', [
                    'clave' => $clave,
                    'name' => $name,
                    'code' => $code,
                    'terminal' => $terminal,
                    'num_tpv' => $num_tpv,
                    'amount' => $amount,
                    'currency' => $currency,
                    'transactionType' => $transactionType,
                    'urlMerchant' => $urlMerchant,
                    'product' => $product,
                    'urlOK' => $urlOK,
                    'urlKO' => $urlKO,
                ]);
            } else {
                abort(404);
            }
        } else {
            abort(405);
        }

    }

    public function ko_payment($id) {
        $tpv_payment = TpvPayment::findOrFail($id);
        $tpv_payment->response_code = '12345';
        $tpv_payment->save();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://ews01.salvadorescoda.com/api/accounts/login",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => array('username' => 'conecta', 'password' => 'escoda00')
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $json = json_decode($response);
        if (!empty($json->token)) {
            $curl2 = curl_init();
            curl_setopt_array($curl2, array(
                CURLOPT_URL => "https://ews01.salvadorescoda.com/api/execute/u_pago_tpv",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => array('token' => $json->token, 'accountid' => '4', 'pn_header_id' => $tpv_payment->header_id, 'pn_importe' => $tpv_payment->price, 'pv_ref_pago' => $tpv_payment->response_code)
            ));
            $response2 = curl_exec($curl2);
            curl_close($curl2);
            $json2 = json_decode($response2);
            if (!$json2->error) {

            }
        }

        return view('tpv_payments.ko_payment', [
            'tpv_payment' => $tpv_payment,
        ]);
    }

    public function ok_payment($id) {
        $tpv_payment = TpvPayment::findOrFail($id);
        $tpv_payment->response_code = '12345';
        $tpv_payment->is_correct = true;
        $tpv_payment->save();

        $body = 'El pedido '.$tpv_payment->code.' está pagado';

        self::send_message(1, 1, 1, $body, false);

        if(true) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://ews01.salvadorescoda.com/api/accounts/login",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => array('username' => 'conecta', 'password' => 'escoda00')
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $json = json_decode($response);
            if (!empty($json->token)) {
                $curl2 = curl_init();
                curl_setopt_array($curl2, array(
                    CURLOPT_URL => "https://ews01.salvadorescoda.com/api/execute/u_pago_tpv",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => array('token' => $json->token, 'accountid' => '4', 'pn_header_id' => $tpv_payment->header_id, 'pn_importe' => $tpv_payment->price, 'pv_ref_pago' => $tpv_payment->response_code)
                ));
                $response2 = curl_exec($curl2);
                curl_close($curl2);
                $json2 = json_decode($response2);
                if (!$json2->error) {

                }
            }
        }

        return view('tpv_payments.ok_payment', [
            'tpv_payment' => $tpv_payment,
        ]);
    }

    private function send_message(int $chat_room_id, int $user_id, int $message_body_type_id, string $body, bool $actions_after_send = true) {
        //RC: Obtenemos el usuario que manda el mensaje
        $user = User::findOrFail($user_id);

        //RC: Obtenemos la sala de chat
        $chat_room = ChatRoom::findOrFail($chat_room_id);
        
        $message = new Message();
        $message->chat_room_id = $chat_room->id;
        $message->message_type_id = $chat_room->message_type_id;
        $message->write_user_id = $user->id;
        switch ($chat_room->message_type_id) {
            case 1:
                $message->from = $user->username;
                break;
            case 2:
                $message->from = '+34931070740';
                break;
        }
        $message->fromName = $user->name;
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
                $body_array = explode('|', $body);
                $file = $body_array[0];
                if(!empty($body_array[1])) {
                    $file_name = $body_array[1];
                } else {
                    $file_name = '';
                }
                $message_body->content = '<a href="'.$file.'" target="_blank"><figure class="image"><img src="'.$file.'" title="'.$file_name.'" class="is-fullwidth"/></figure></a>';
                break;
            case 3:
                $body_array = explode('|', $body);
                $file = $body_array[0];
                if(!empty($body_array[1])) {
                    $file_name = $body_array[1];
                } else {
                    $file_name = '';
                }
                $message_body->content = '<a href="'.$file.'" target="_blank">Descargar <span class="fa fa-upload"></span> '.$file_name.'</a>';
                break;
        }
        $message_body->save();

        //RC: aumentamos el unread de los otros usaurios
        DB::update("UPDATE user_chat_room SET unread = unread + 1 WHERE chat_room_id = ".$chat_room->id." AND user_id <> ".$user->id);

        //RC: generamos el evento al canal pertinente
        if($chat_room->departments->count() > 0) {
            foreach($chat_room->departments as $department) {
                broadcast(new NewMessage($message->load('message_body', 'write_user', 'read_user', 'account', 'contact'), 'App.Department.'.$department->id));
            }
        } elseif($chat_room->users()->count() > 0) {
            foreach($chat_room->users as $user) {
                broadcast(new NewMessage($message->load('message_body', 'write_user', 'read_user', 'account', 'contact'), 'App.User.'.$user->id));
            }
        }

        return $message->load('message_body', 'write_user', 'read_user', 'account', 'contact');
    }
}
