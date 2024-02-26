<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Http\Requests\StoreCandidateRequest;
use App\Http\Requests\UpdateCandidateRequest;
use App\Http\Resources\CandidateResource;
use App\Models\Skill;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CandidateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 10);
            $query = Candidate::query();

            // Búsqueda
            if ($request->filled('search')) {
                $searchTerm = $request->query('search');
                $query->where(function ($subquery) use ($searchTerm) {
                    $subquery->where('full_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('skills', 'like', '%' . $searchTerm . '%')
                        ->orWhere('certifications', 'like', '%' . $searchTerm . '%');
                });
            }

            // Filtros
            $filters = [
                'full_name', 'gender', 'education', 'status', 'date_of_birth', 'skills', 'certifications',
            ];

            foreach ($filters as $filter) {
                if ($request->filled($filter)) {
                    $query->where($filter, $request->query($filter));
                }
            }

            // Ordenación
            if ($request->filled('sort_by') && $request->filled('sort_order')) {
                $sortBy = $request->query('sort_by');
                $sortOrder = $request->query('sort_order');
                $query->orderBy($sortBy, $sortOrder);
            }

            $candidates = $query->paginate($perPage);

            $paginationData = [
                'total' => $candidates->total(),
                // 'per_page' => $candidates->perPage(),
                // 'current_page' => $candidates->currentPage(),
                // 'last_page' => $candidates->lastPage(),
                // 'from' => $candidates->firstItem(),
                // 'to' => $candidates->lastItem(),
                // 'next_page_url' => $candidates->nextPageUrl(),
                // 'prev_page_url' => $candidates->previousPageUrl(),
                // 'path' => $candidates->path(),
            ];

            return response()->json(['data' => $candidates, 'pagination' => $paginationData], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while getting the candidate list!',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created candidate instance in storage.
     *
     * @param StoreCandidateRequest $request
     * @return JsonResponse
     */
    public function store(StoreCandidateRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $user = auth()->user();

        if (!$user->hasRole('candidate')) {
            return response()->json(['error' => 'User does not have the candidate role'], 403);
        }

        try {
            // Lógica para la generación de nombres de archivos del candidato
            $cvName = 'cv_file_' . $user->id . $this->generateFileName($request->cv_file);
            $photoName = 'photo_file_' . $user->id . $this->generateFileName($request->photo_file);
            $bannerName = 'banner_file_' . $user->id . $this->generateFileName($request->banner_file);

            // Crear instancia en la tabla 'candidates'
            $candidate = Candidate::create([
                'user_id' => $user->id,
                'full_name' => $validatedData['full_name'],
                'gender' => $validatedData['gender'],
                'date_of_birth' => $validatedData['date_of_birth'],
                'address' => $validatedData['address'],
                'phone_number' => $validatedData['phone_number'],
                'work_experience' => $validatedData['work_experience'],
                'education' => $validatedData['education'],
                'certifications' => $validatedData['certifications'],
                'languages' => $validatedData['languages'],
                'references' => $validatedData['references'],
                'expected_salary' => $validatedData['expected_salary'],
                'cv_path' => 'candidates/cvs/' .  $cvName,
                'photo_path' => 'candidates/profile_photos/' .  $photoName,
                'banner_path' => 'candidates/banners/' .  $bannerName,
                'social_networks' => $validatedData['social_networks'],
                'status' => $validatedData['status'],
            ]);

            // Almacenamiento de archivos del candidato en el directorio correspondiente
            $this->storeFile($cvName, $request->cv_file, 'candidate_uploads/cvs');
            $this->storeFile($photoName, $request->photo_file, 'candidate_uploads/profile_photos');
            $this->storeFile($bannerName, $request->banner_file, 'candidate_uploads/banners');

            // Validar y asociar habilidades al candidato
            $this->attachSkills($request, $candidate);

            return response()->json(['data' => $candidate, 'message' => 'Candidate Created Successfully!'], 201);
        } catch (QueryException $e) {
            return $this->handleDatabaseError($e);
        } catch (\Exception $e) {
            return $this->handleGenericError($e);
        }
    }


    private function attachSkills(Request $request, Candidate $candidate): void
    {
        if ($request->filled('skills')) {
            $skills = explode(',', $request->input('skills'));

            foreach ($skills as $skillName) {
                $this->validateAndAttachSkill($candidate, $skillName);
            }
        }
    }

    private function validateAndAttachSkill(Candidate $candidate, $skillName)
    {
        $skillName = trim($skillName);
        $skill = Skill::where('name', $skillName)->first();

        if ($skill) {
            $candidate->addSkill($skill->id);
        } else {
            throw new \Exception("Skill '$skillName' not found in the database.");
        }
    }

    private function handleDatabaseError(QueryException $e): JsonResponse
    {
        return response()->json([
            'error' => 'An error occurred in the database while creating the candidate!',
            'details' => $e->getMessage()
        ], 500);
    }

    private function handleGenericError(\Exception $e): JsonResponse
    {
        return response()->json([
            'error' => 'An error occurred while creating the candidate!',
            'details' => $e->getMessage()
        ], 500);
    }

    /**
     * Genera un nombre de archivo único.
     *
     * @param \Illuminate\Http\UploadedFile|null $file
     * @return string|null
     */
    private function generateFileName($file): ?string
    {
        return $file ? Str::random(10) . "." . $file->getClientOriginalExtension() : null;
    }

    /**
     * Almacena un archivo en el disco.
     *
     * @param string $fileName
     * @param \Illuminate\Http\UploadedFile $file
     * @return void
     */
    private function storeFile($fileName, $file, $directory): void
    {
        Storage::disk('public')->put("$directory/$fileName", file_get_contents($file));
    }

    /**
     * Add skills to the candidate.
     *
     * @param Request $request
     * @param Candidate $candidate
     * @param Skill $skill
     * @return JsonResponse
     */
    public function addSkill(Request $request, Candidate $candidate)
    {
        try {
            $skillsNotFound = [];
            $skills = $request->input('skills');

            foreach ($skills as $skillName) {
                $skillName = trim($skillName);

                // Validar que la habilidad exista en la base de datos
                $skill = Skill::where('name', $skillName)->first();

                if ($skill) {
                    $candidate->addSkill($skill->id);
                } else {
                    $skillsNotFound[] = $skillName;
                }
            }

            if (!empty($skillsNotFound)) {
                return response()->json(['error' => 'Skills not found in the database.', 'skills_not_found' => $skillsNotFound], 422);
            }

            return response()->json(['message' => 'Skills added to candidate successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while adding skills to candidate.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove skills from the candidate.
     *
     * @param Request $request
     * @param Candidate $candidate
     * @param Skill $skill
     * @return JsonResponse
     */
    public function removeSkill(Request $request, Candidate $candidate, Skill $skill)
    {
        try {
            // Validar la existencia de la habilidad
            if ($candidate->skills->contains($skill->id)) {
                $candidate->removeSkill($skill->id);
                return response()->json(['message' => 'Skill removed from candidate successfully!'], 200);
            } else {
                return response()->json(['error' => 'Skill not found in candidate\'s profile!'], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while removing skill from candidate!',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified candidate profile.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            // Obtener el candidato por user_id con la relación de usuario cargada de forma ansiosa
            $candidate = Candidate::with('user')->where('user_id', $id)->first();

            // Verificar si el usuario autenticado tiene el rol 'candidate' o 'admin'
            $authenticatedUser = auth()->user();
            if (!($authenticatedUser->hasRole('candidate') && $authenticatedUser->id === $candidate->user_id) && !$authenticatedUser->hasRole('admin')) {
                return response()->json(['error' => 'Unauthorized access to candidate profile'], 403);
            }

            // Obtener las habilidades del candidato
            $skills = $candidate->skills;

            // Transformar el candidato a un recurso CandidateResource
            $candidateResource = new CandidateResource($candidate);

            return response()->json([
                'message' => 'Candidate Profile Successfully Obtained!',
                'data' => [
                    'candidate' => $candidateResource,
                    'skills' => $skills,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Manejar el caso en el que no se encuentra al candidato
            return response()->json([
                'error' => 'Candidate not found.',
                'details' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            // Manejar cualquier otra excepción y devolver una respuesta de error
            return response()->json([
                'error' => 'An error occurred while getting the candidate profile.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param UpdateCandidateRequest $request
     * @param Candidate $candidate
     * @return JsonResponse
     */
    public function update(UpdateCandidateRequest $request, Candidate $candidate): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            // Validar que el usuario autenticado tenga permisos para actualizar este candidato
            $authenticatedUser = auth()->user();
            if (!$authenticatedUser->hasRole('candidate') || $authenticatedUser->id !== $candidate->user_id) {
                return response()->json(['error' => 'Unauthorized to update this candidate'], 403);
            }

            // Realizar la actualización
            $candidate->update($validatedData);

            // Obtener la instancia actualizada del candidato
            $updatedCandidate = $candidate->fresh();

            // Registrar actividad o log si es necesario

            return response()->json(['data' => $updatedCandidate, 'message' => 'Candidate updated successfully!'], 200);
        } catch (QueryException $e) {
            // Manejar errores de base de datos
            return response()->json([
                'error' => 'An error occurred in the database while updating the candidate!',
                'details' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Manejar otros errores
            return response()->json([
                'error' => 'An error occurred while updating the candidate!',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Candidate $candidate
     * @return JsonResponse
     */
    public function destroy(Candidate $candidate): JsonResponse
    {
        try {
            // Validar que el usuario autenticado tenga permisos para eliminar este candidato
            $authenticatedUser = auth()->user();
            if (!$authenticatedUser->hasRole('admin') && $authenticatedUser->id !== $candidate->user_id) {
                return response()->json(['error' => 'Unauthorized to delete this candidate'], 403);
            }

            // Iniciar una transacción de base de datos
            DB::beginTransaction();

            // Eliminar al candidato y al usuario asociado
            $candidate->delete();
            $candidate->user()->delete();

            // Commit de la transacción
            DB::commit();

            return response()->json(['message' => 'Candidate deleted!'], 200);
        } catch (\Exception $e) {
            // Rollback de la transacción en caso de error
            DB::rollBack();

            return response()->json([
                'error' => 'An error occurred while deleting the candidate and associated user!',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
