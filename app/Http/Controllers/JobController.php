<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use Illuminate\Http\JsonResponse;

class JobController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $jobs = Job::with(['company', 'jobCategory', 'jobType'])->get();
            return response()->json(['data' => $jobs], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener la lista de trabajos.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreJobRequest $request
     * @return JsonResponse
     */
    public function store(StoreJobRequest $request)
    {
        try {
        // Validación y creación del trabajo
        $validatedData = $request->validated();
        
        // Verificar si se proporcionó un plan de suscripción
        $subscriptionPlanId = $request->input('subscription_plan_id', null);

        // Lógica para asignar el plan de suscripción por defecto si no se proporciona uno
        if (is_null($subscriptionPlanId)) {
            // Asignar el ID del plan básico (ajusta esto según tu lógica y estructura de datos)
            $defaultSubscriptionPlanId = 1; // Por ejemplo, el ID del plan básico
            $validatedData['subscription_plan_id'] = $defaultSubscriptionPlanId;
        }

        // Crear el trabajo con los datos validados
        $job = Job::create($validatedData);

        // Resto de la lógica, si es necesario...

        return response()->json(['message' => 'Job created successfully', 'data' => $job], 201);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Error al crear la oferta de trabajo.'], 500);
    }
    }

    /**
     * Display the specified resource.
     *
     * @param Job $job
     * @return JsonResponse
     */
    public function show(Job $job): JsonResponse
    {
        try {
            return response()->json(['data' => $job], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener el trabajo.'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateJobRequest $request
     * @param Job $job
     * @return JsonResponse
     */
    public function update(UpdateJobRequest $request, Job $job)
    {
        try {
        // Validación y actualización del trabajo
        $validatedData = $request->validated();

        // Verificar si se proporcionó un nuevo plan de suscripción
        $subscriptionPlanId = $request->input('subscription_plan_id', null);

        // Lógica para actualizar el plan de suscripción si se proporciona uno
        if (!is_null($subscriptionPlanId)) {
            $job->subscription_plan_id = $subscriptionPlanId;
            // Puedes agregar más lógica según tus necesidades...
        }

        // Actualizar el trabajo con los datos validados
        $job->update($validatedData);

        // Resto de la lógica, si es necesario...

        return response()->json(['message' => 'Job updated successfully', 'data' => $job], 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el trabajo.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Job $job
     * @return JsonResponse
     */
    public function destroy(Job $job): JsonResponse
    {
        try {
            $job->delete();
            return response()->json(['message' => 'Trabajo eliminado con éxito.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar el trabajo.'], 500);
        }
    }
}
