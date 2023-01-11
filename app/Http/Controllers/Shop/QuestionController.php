<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\Question;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class QuestionController extends Controller
{
    public function addQuestion(Request $request)
    {
        $questionHandler = collect($request->input('fields'));
        $question = new Question();
        $question->name = $questionHandler['name'];
        $question->email = $questionHandler['email'];
        $question->question = $questionHandler['question'];
        $question->save();
        return new JsonResponse([
            'data' => 'success'
        ]);
    }
}
