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
        try {
            $apiKey = env('API_KEY');

            $response = Http::timeout(120)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->post('https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image', [
                'text_prompts' => [[
                    'text' => $request->prompt
                ]]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                foreach ($data['artifacts'] as $index => $image) {
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
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка: ' . $response->status() . ' - ' . $response->body()
                ], 400);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
}