<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Por ejemplo, un usuario puede ver una factura si es el propietario de la factura o si es un administrador
        return $user->id === $invoice->user_id || $user->is_admin;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Un usuario puede crear facturas
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // Por ejemplo, un usuario puede actualizar su propia factura
        return $user->id === $invoice->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Por ejemplo, un usuario puede eliminar su propia factura
        return $user->id === $invoice->user_id;
    }
}
