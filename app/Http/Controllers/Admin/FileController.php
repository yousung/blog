<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FileController extends Controller
{
    public function store(Request $request)
    {
        if ($request->has('files')) {
            $files = $request->file('files');
            $fs = [];
            foreach ($files as $file) {
               try{
                   $fileOriginal = $file->getClientOriginalName();
                   $fileName = \str_random().filter_var($fileOriginal, FILTER_SANITIZE_URL);
                   $imgPath = image_path($fileName);
                   $pro = null;

                   $disk = \Storage::disk('s3');
                   $disk->put($imgPath, file_get_contents($file), 'public');

                   $imgUrl = $disk->url($imgPath);
                   $tempImage = \Image::make($imgUrl);
                   $pro = image_crop($imgUrl, $fileSize ?? ($tempImage->width().'x'.$tempImage->height()));
                   $disk->delete($imgPath);
                   $fs[] = $pro;
               }catch (\Exception $e){
                   return response()->json(['msg' => $e->getMessage()], $e->getCode(), JSON_PRETTY_PRINT;
               }
            }

            if (count($fs)) {
                return \response()->json([
                    'code' => 'success',
                    'data' => [
                        'pro' => $fs,
                    ],
                ], 200, [], JSON_PRETTY_PRINT);
            } else {
                return \response()->json([
                    'code' => 'error',
                    'msg' => '파일 캐시화에 실패하였습니다.',
                ], 500, [], JSON_PRETTY_PRINT);
            }
        }

        return \response()->json([
            'code' => 'error',
            'msg' => '이미지가 없습니다.',
        ], 500, [], JSON_PRETTY_PRINT);
    }
}
