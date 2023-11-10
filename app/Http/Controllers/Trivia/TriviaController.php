<?php

namespace App\Http\Controllers\Trivia;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FileUploadController;
use App\Http\Requests\Trivia\CreateTriviaRequest;
use App\Http\Requests\Trivia\UpdateTriviaRequest;
use App\Http\Requests\SubmitAnswersRequest;
use App\Models\Answer;
use App\Models\ImageQuestion;
use App\Models\Question;
use App\Models\Trivia;
use App\Models\UserAnswers;
use App\Models\Score;
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

        // Verificar si ya hay una trivia asociada al LearningInfo
        $existingTrivia = Trivia::where('learning_info_id', $data['learning_info_id'])->first();

        if ($existingTrivia) {
            
            return response()->json(['message' => 'Ya existe una trivia asociada a este LearningInfo'], 400);
        }
    
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

    /*-- Aquí comienza la lógica para mostrar las trivias :)--*/
    public function getTriviaQuestions(Request $request, $triviaId)
    {
            $trivia = Trivia::find($triviaId);
            
            if (!$trivia) {
                return response()->json(['message' => 'No se encontró la trivia'], 404);
            }

            // Obtén hasta 5 preguntas aleatorias de la trivia
            $questions = $trivia->questions()->inRandomOrder()->take(5)->get();

            $questions->each(function ($question) {
                $answers = $question->answers;
            
                // Selecciona una cantidad específica de respuestas correctas (buenas) y respuestas incorrectas (malas)
                $correctAnswers = $answers->where('is_correct', 1)->shuffle()->take(1); // Cambia el número 1 al número deseado de respuestas correctas
                $incorrectAnswers = $answers->where('is_correct', 0)->shuffle()->take(3); // Cambia el número 3 al número deseado de respuestas incorrectas
            
                // Mezcla todas las respuestas y selecciona una aleatoria de todas
                $shuffledAnswers = $correctAnswers->concat($incorrectAnswers)->shuffle();
            
                $question->answers = $shuffledAnswers->map(function ($answer) {
                    return [
                        'id' => $answer->id,
                        'text' => $answer->text,
                        'is_correct' => $answer->is_correct,
                    ];
                });
            
                // Verifica si la pregunta tiene una `question_image` y agrega la URL de la imagen al mismo nivel que el `question_text`
                if ($question->imageQuestion) {
                    $question->image_path = $question->imageQuestion->image_path;
                }
     });
    
        // Construye la respuesta JSON
         $response = [
             'trivia' => [
                 'name' => $trivia->name,
                 'description' => $trivia->description,
             ],
             'questions' => $questions->map(function ($question) {
                 $questionData = [
                     'id' => $question->id,
                     'trivia_id' => $question->trivia_id,
                     'question_text' => $question->question_text,
                     'answers' => $question->answers,
                 ];
             
                 // Si la pregunta tiene una imagen, agrega la URL al mismo nivel que el `question_text`
                 if ($question->image_path) {
                     $questionData['image_path'] = $question->image_path;
                 }
             
                 return $questionData;
             }),
         ];
     
         return response()->json($response);
    }

    
    public function submitAnswers(SubmitAnswersRequest $request)
    {
        $user = auth()->user();
        $data = $request->all();
    
        // Calcula el puntaje (5 puntos por respuesta correcta)
        $score = 0;
    
        // Asume que las respuestas están en $data['answers']
        foreach ($data['answers'] as $answer) {
            $answerModel = Answer::find($answer['answer_id']);
    
            if ($answerModel && $answerModel->is_correct) {
                $score += 5;
            }
    
            // Guarda la respuesta relacionada al usuario
            $user->answers()->attach($answer['answer_id'], [
                'is_correct' => $answerModel->is_correct,
                'question_id' => $answerModel->question_id,
            ]);
        }
    
        // Asume que obtuviste el trivia_id de la solicitud
        $triviaId = $data['trivia_id'];
    
        // Busca el puntaje actual del usuario para este trivia
        $userScore = Score::where('user_id', $user->id)
            ->where('trivia_id', $triviaId)
            ->first();
    
        if (!$userScore) {
            // Si no existe un puntaje para esta trivia, crea uno nuevo
            $userScore = new Score([
                'user_id' => $user->id,
                'trivia_id' => $triviaId,
                'score' => $score,
            ]);
        } else {
            // Si ya existe un puntaje, verifica si el nuevo puntaje es más alto y actualízalo
            if ($score > $userScore->score) {
                $userScore->score = $score;
            }
        }
    
        $userScore->save();
    
        return response()->json(['message' => 'Respuestas guardadas exitosamente.', 'score'=> $score], 200);
    }
    
    // Eliminación de la trivia basándonos en el id del Learning
    public function destroy($triviaId)
    {
        $trivia = Trivia::find($triviaId);

        if(!$trivia) {
            return response()->json(['message'=> 'Trivia no encontrada 🐺']);
        }

        // Llamamos al método delete (Debería de eliminarse en cascada con el ELOQUENT)
        $trivia->delete();

        return response()->json(['message'=>'Se eliminó correctamente 🤬']);
    }
    
    // Index de trivias
    public function index(Request $request)
    {
        // Especifica la cantidad de trivias por página 
        $perPage = $request->input('per_page', 10);
    
        // Obtén las trivias con paginación
        $trivias = Trivia::paginate($perPage);
    
        // Construye la respuesta JSON
        $response = $trivias->map(function ($trivia) {
            return [
                'id' => $trivia->id,
                'name' => $trivia->name,
                'description' => $trivia->description,
                'learning_id' => $trivia->learning_info_id,
            ];
        });
    
        return response()->json(['trivias' => $response]);
    }
    
    public function indexScore(Request $request)
    {
        // Obtén el ID de la trivia desde la solicitud
        $triviaId = $request->query('trivia_id');
    
        // Construye la consulta base
        $query = Score::query();
    
        // Si se proporciona un ID de trivia, aplica el filtro
        if ($triviaId) {
            $query->where('trivia_id', $triviaId);
        }
    
        // Obtén los puntajes paginados
        $scores = $query->paginate(10);
    
        // Respuesta JSON formateada
        $formattedScores = $scores->map(function ($score) {
            $userName = optional($score->user->profile)->name;
            $userEmail = $score->user->email;
            $scoreValue = $score->score;
            $triviaName = $score->trivia->name;
    
            return [
                'user_name' => $userName,
                'user_email' => $userEmail,
                'score' => $scoreValue,
                'trivia_name' => $triviaName,
            ];
        });
    
        return response()->json(['scores' => $formattedScores]);
    }
    

}
