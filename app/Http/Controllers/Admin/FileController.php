<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FileController extends Controller
{
    public function store(Request $request)
    {
        if ($file = $request->file('file')) {
            $fileOriginal = $file->getClientOriginalName();
            $fileSize = $request->input('fileSize');
            $fileName = \str_random().filter_var($fileOriginal, FILTER_SANITIZE_URL);
            $imgPath = image_path($fileName);
            $thumb = null;
            $pro = null;

            $disk = \Storage::disk('s3');
            $disk->put($imgPath, file_get_contents($file), 'public');
            $tempImage = \Image::make($disk->url($imgPath));

            $imgUrl = $disk->url($imgPath);
            $thumb = image_crop($imgUrl, '190x120');
            $pro = image_crop($imgUrl, $fileSize ?? ($tempImage->width().'x'.$tempImage->height()));
            $disk->delete($imgPath);

            if ($thumb && $pro) {
                return \response()->json([
                    'code' => 'success',
                    'data' => [
                        'thumb' => $thumb,
                        'pro' => $pro,
                    ],
                ], 200, [], JSON_PRETTY_PRINT);
            } else {
                return \response()->json([
                    'code' => 'error',
                    'msg' => '파일 캐시화에 실패하였습니다.',
                ], 500, [], JSON_PRETTY_PRINT);
            }
        }
    }
}
