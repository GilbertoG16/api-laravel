<?php

namespace App\Http\Controllers\Trivia;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileUploadController;
use App\Http\Requests\Trivia\CreateTriviaRequest;
use App\Http\Requests\Trivia\UpdateTriviaRequest;
use App\Models\Answer;
use App\Models\ImageQuestion;
use App\Models\Question;
use App\Models\Trivia;
use App\Services\FirebaseStorageService;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Storage;
use Illuminate\Http\Request;

class TriviaController extends Controller
{
    protected $firebaseStorageService;

    public function __construct(FirebaseStorageService $firebaseStorageService)
    {
        $this->firebaseStorageService = $firebaseStorageService;
    }


    public function createTrivia(CreateTriviaRequest $request)
    {
        $data = $request->validated();
    
        $trivia = Trivia::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'learning_info_id' => $data['learning_info_id'],
        ]);
    
        foreach ($data['questions'] as $questionData) {
            $question = Question::create([
                'trivia_id' => $trivia->id,
                'question_text' => $questionData['question_text'],
            ]);
    
            foreach ($questionData['answers'] as $answerData) {
                Answer::create([
                    'question_id' => $question->id,
                    'text' => $answerData['text'],
                    'is_correct' => $answerData['is_correct'],
                ]);
            }
    
            if (isset($questionData['image_path'])) {
                $publicUrl = $this->firebaseStorageService->uploadFile($questionData['image_path'], 'trivias/' . $trivia->id . '/' . $question->id . '/media/');
    
                ImageQuestion::create([
                    'question_id' => $question->id,
                    'image_path' => $publicUrl,
                ]);
    
                $questionData['image_path'] = $publicUrl;
            }
        }
        $trivia['questions'] = $data['questions'];
    
        return response()->json(['message' => 'Trivia creada exitosamente 😍', 'trivia' => $trivia], 200);
    }

    public function updateTrivia(UpdateTriviaRequest $request, $triviaId)
    {
        $data = $request->validated();
    
        // Asegúrate de que la trivia exista por su ID.
        $trivia = Trivia::find($triviaId);
    
        if (!$trivia) {
            // Manejo del caso en el que no se encontró la trivia.
            return response()->json(['message' => 'Trivia no encontrada'], 404);
        }
    
        // Actualiza los campos generales de la trivia según los datos proporcionados en $data.
        if (isset($data['name'])) {
            $trivia->name = $data['name'];
        }
    
        if (isset($data['description'])) {
            $trivia->description = $data['description'];
        }
    
        // Guardamos la trivia con las actualizaciones.
        $trivia->save();
    
        // Lógica para administrar las preguntas y respuestas si se proporcionan en la solicitud.
        if (isset($data['questions']) && is_array($data['questions'])) {
            foreach ($data['questions'] as $questionData) {
                if (isset($questionData['id'])) {
                    // Si se proporciona un ID de pregunta, actualiza la pregunta existente.
                    $question = Question::find($questionData['id']);
    
                    if ($question) {
                        $question->question_text = $questionData['question_text'];
                        $question->save();
    
                // Lógica para actualizar la imagen de la pregunta.
                if (isset($questionData['image_path'])) {
                    // Verifica si ya existe un registro de imagen para la pregunta.
                    $imageQuestion = ImageQuestion::where('question_id', $question->id)->first();
                
                    if ($imageQuestion) {
                        // Si existe un registro de imagen, elimina la imagen anterior si existe.
                        if ($imageQuestion->image_path) {
                            $this->firebaseStorageService->deleteFileByUrl($imageQuestion->image_path);
                        }
                    
                        // Sube la nueva imagen y actualiza la URL.
                        $publicUrl = $this->firebaseStorageService->uploadFile($questionData['image_path'], 'trivias/' . $trivia->id . '/' . $question->id . '/media/');
                        $imageQuestion->image_path = $publicUrl;
                        $imageQuestion->save();
                    } else {
                        // Si no existe un registro de imagen, crea uno nuevo.
                        $publicUrl = $this->firebaseStorageService->uploadFile($questionData['image_path'], 'trivias/' . $trivia->id . '/' . $question->id . '/media/');
                        ImageQuestion::create([
                            'question_id' => $question->id,
                            'image_path' => $publicUrl,
                        ]);
                    }
                
                    // Actualiza el campo image_path en $questionData con la nueva URL.
                    $questionData['image_path'] = $publicUrl;
                }

    
                        // Lógica para actualizar las respuestas.
                        if (isset($questionData['answers']) && is_array($questionData['answers'])) {
                            foreach ($questionData['answers'] as $answerData) {
                                if (isset($answerData['id'])) {
                                    // Si se proporciona un ID de respuesta, actualiza la respuesta existente.
                                    $answer = Answer::find($answerData['id']);
                                    if ($answer) {
                                        $answer->text = $answerData['text'];
                                        $answer->is_correct = $answerData['is_correct'];
                                        $answer->save();
                                    }
                                } else {
                                    // Si no se proporciona un ID de respuesta, creamos una nueva respuesta.
                                    Answer::create([
                                        'question_id' => $question->id,
                                        'text' => $answerData['text'],
                                        'is_correct' => $answerData['is_correct'],
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    // Si no se proporciona un ID de pregunta, creamos una nueva pregunta.
                    $newQuestion = Question::create([
                        'trivia_id' => $trivia->id,
                        'question_text' => $questionData['question_text'],
                    ]);
    
                    // Lógica para crear respuestas para la nueva pregunta.
                    if (isset($questionData['answers']) && is_array($questionData['answers'])) {
                        foreach ($questionData['answers'] as $answerData) {
                            Answer::create([
                                'question_id' => $newQuestion->id,
                                'text' => $answerData['text'],
                                'is_correct' => $answerData['is_correct'],
                            ]);
                        }
                    }
                }
            }
        }
    
        return response()->json(['message' => 'Trivia actualizada exitosamente 😍', 'trivia' => $trivia], 200);
    }
        
}
