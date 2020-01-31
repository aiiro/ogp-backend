<?php

namespace App\Http\Controllers;

use App\Models\Message;
use DB;
use Exception;
use Illuminate\Http\Request;
use Storage;
use Str;

class MessageController extends Controller
{

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** @var Message $message */
        $message = Message::find($id);

        return response()->json([
            'message' => $message->content,
            'url' => config('app.image_url') . '/' . $message->file_path
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        $params = $request->json()->all();

        list(, $image) = explode(';', $params['image']);
        list(, $image) = explode(',', $image);
        $decodedImage = base64_decode($image);

        $content = $params['message'];

        $id = DB::transaction(function () use ($decodedImage, $content) {
            $id = Str::uuid();
            $file = $id->toString() . '.jpg';

            Message::create([
                'id' => $id,
                'content' => $content,
                'file_path' => $file,
            ]);

            $isSuccess = Storage::disk('s3')->put($file, $decodedImage);
            if (!$isSuccess) {
                throw new Exception('ファイルアップロード時にエラーが発生しました。');
            }

            Storage::disk('s3')->setVisibility($file, 'public');

            return $id;
        });

        return response()->json($id);
    }
}
