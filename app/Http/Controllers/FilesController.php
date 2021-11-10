<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FilesController extends Controller
{
    public function upload(Request $request) {
        $validations = [
            'file' => 'mimes:jpg,jpeg,png,mp4,mp3,mpeg,wav,doc,docx,xls,xlsx,pdf'
        ];

        $data = $request->all();

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            $disk = 'public';
            $path = 'files/'.date('Y').'/'.date('m');
            $index = 'file';
            
            return self::upload_file($request, $disk, $path, $index);
        }

    }

    public function upload_paste_image(Request $request) {

        Validator::extend('is_png',function($attribute, $value, $params, $validator) {
            $image = str_replace('data:image/png;base64,', '', $value);
            $image = str_replace(' ', '+', $image);
            $image = base64_decode($image);
            $f = finfo_open();
            $result = finfo_buffer($f, $image, FILEINFO_MIME_TYPE);
            return $result == 'image/png';
        });

        $validations = [
            'file' => 'is_png'
        ];

        $data = $request->all();

        //RC: generamos el objeto para validar los datos
        $validator = \Validator::make($data, $validations);

        if ($validator->fails()) {
            //RC: si la validación no es correta tenemos que el listado de errores.
            return ['errors' => $validator->errors()];
        } else {
            $disk = 'public';
            $path = 'files/'.date('Y').'/'.date('m');
            $index = 'file';
            return self::upload_file_base64($request, $disk, $path, $index);
        }

    }

    private function upload_file($request, $disk, $path, $index) {
        $name =  $request->file($index)->getClientOriginalName();
        $i = 1;
        while(self::file_extist_help($path, $name, $disk)) {
            $name = $i.'-'.$request->file($index)->getClientOriginalName();
            $i++;
        }

        $path_file = $request->file($index)->storeAs($path, $name);

       // $request->file($index)->storeAs($path, $name, 'ftp');
        

        return '/storage/'.$path_file;
    }

    private function upload_file_base64($request, $disk, $path, $index) {
        $name =  date('YmdHis').'.png';
        $i = 1;
        while(self::file_extist_help($path, $name, $disk)) {
            $name = $i.'-'.$request->file($index)->getClientOriginalName();
            $i++;
        }

        $data = $request->all();

        $image = str_replace('data:image/png;base64,', '', $data[$index]);
        $image = str_replace(' ', '+', $image);

        Storage::put($path.'/'.$name, base64_decode($image));

        return '/storage/'.$path.'/'.$name;
    }

    private function file_extist_help($path, $name, $disk) {
        return Storage::exists($path.'/'.$name);
    }
}
