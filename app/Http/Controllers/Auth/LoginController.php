<?php

namespace App\Http\Controllers\Auth;

use App\BreakTimeUser;
use App\Events\LoginUser;
use App\Events\StartBreakTimeUser;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\User;
use App\UserSession;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function username() {
        return 'username';
    }

    public function login(Request $request) {
        $request->validate([
            "username"           =>    "required|max:100",
            "password"        =>    "required|min:6"
        ]);

        $userCredentials = $request->only('username', 'password');

        //RC: Validamos si podemos logear el usuario
        if (Auth::attempt($userCredentials)) {
            //RC: Si el usuario existe generamos un nuevo token para el api
            $user = Auth::user();
            $user->api_token = Str::random(100);
            $user->save();

            Cookie::queue('gctoken', base64_encode($user->api_token), 3600);

            //RC: Miramos si tenemos compañia seleccionada
            if(empty($user->company_id) || (!empty($user->company_id) && $user->companies()->where('id', $user->company_id)->count() === 0 )) {
                //RC: Si no tenemos compañía seleccionamos la primera
                $user->company_id = $user->companies()->orderBy('id', 'asc')->first()->id;
                $user->save();
            }

            if(!empty($request->latitude)) {
                $latitude = $request->latitude;
            } else {
                $latitude = '';
            }

            if(!empty($request->longitude)) {
                $longitude = $request->longitude;
            } else {
                $longitude = '';
            }

            //RC: Si solo tenemos una extensión iniciamos la sesión con ese número.
            if ($user->user_extensions()->count() == 0) {
                $extension = $user->extension;
            } else {
                $extension = null;
            }

            //RC: Guardamos el registro del inicio de sesión
            start_user_session($user, $latitude, $longitude, $extension);

            //RC: Emitimos el evento de login de usuario
            broadcast(new LoginUser($user));

            if(empty($user->signin_break_time_id)) {
                //RC: Si no tenemos un descanso inicial y tenemos extensión tenemos que quitar la pausa de todas las colas
                if(!empty($user->extension)) {
                    unpause_all_extension($user->extension);
                
                    if($user->paused_queues()->count() > 0) {
                        foreach($user->paused_queues as $queue) {
                            pause_queue($queue->number, $user->extension);
                        }
                    }
                }
            } else {
                //RC: Si tenemos descanso inicial tenemos que pausar la cola
                if(!empty($user->extension)) {
                    pause_all_extension($user->extension);
                }

                $break_time_user = new BreakTimeUser();
                $break_time_user->break_time_id = $user->signin_break_time_id;
                $break_time_user->user_id = $user->id;
                $break_time_user->start = date('YmdHis');
                $break_time_user->save();

                broadcast(new StartBreakTimeUser($break_time_user, $user));
            }

            //RC: set user session
            return redirect()->intended('dashboard');
        }
        else {
            return back()->with('error', 'Whoops! invalid username or password.');
        }
    }
}
