<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Str;

class AiImageController extends Controller
{
    public function index() {
        # Можно было вынести в Middleware
        if(!session()->has('user_id')) {
            session(['user_id' => Str::uuid()->toString()]);
        }
        $images = History::where('user_id', session('user_id'))
                        ->orderBy('created_at', 'desc')
                        ->get();

        return view('home', compact('images'));
    }

    public function generate(Request $request)
    {
        # Никак не проверяется наличие записи user_id в сессии
        # Бизнес-логика в контроллерах плохая практика - используй паттерн Service Layer
        try {
            $apiKey = env('API_KEY');

            # Методы общения с внешним апи лучше вынести в отдельный класс
            # Высокий таймаут как решение длинного запроса - костыль. Используй асинхроншину
            $response = Http::timeout(120)->with([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post('https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image', [
                'text_prompts' => [[
                    'text' => $request->prompt # Нет валидации Request
                ]]
            ]);

            # Я как то уже говорил о всей бессисленности такого большого if-else и как его можно отрефакторить
            if ($response->successful()) {
                # Эту логику можно разбить на методы
                $data = $response->json();

                foreach ($data['artifacts'] as $index => $image) {
                    # Имена лучше хешировать для вида
                    $filename = "image_" . time() . ".png";
                    $path = "images/{$filename}";

                    Storage::disk('public')->put($path, base64_decode($image['base64']));

                    $history = new History();
                    $history->user_id = session('user_id');
                    $history->prompt = $request->prompt;
                    $history->image_path = $path;
                    $history->save();

                    return response()->json(['success' => true]);
                }
            } else {
                // Выбрасывай исключения и обрабатывай их в bootstrap/app.php
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка: ' . $response->status() . ' - ' . $response->body()
                ], 400);
            }
        } catch (\Exception $e) {
            # Использование try-catch похвально, но здесь это бессмысленно
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }

    }
}
